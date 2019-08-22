# Limber

A super minimal HTTP router that doesn't get in your way.

Limber is intended for advanced users who are comfortable setting up their own framework and pulling in packages best suited for their particular use case.

## Limber *does not* ship with

* PSR-7 implementation
* An ORM
* View rendering/template engine
* Container
* Event/subscriber system
* Configuration file management
* Session and cookie management
* Cache library

These are all application implementation details that are best suited for you to decide, dear developer.

## Installation

```bash
composer require nimbly/Limber
```

## Quick start

```php

// Create Application instance with a Router
$application = new Limber\Application(
	$router
);

// Dispatch a PSR-7 ServerRequest instance
$response = $application->dispatch(
	$serverRequest
);

// Send the Response
$application->send($response);
```

## PSR-7
Limber does not ship with a PSR-7 (HTTP Message) implementation so you will need to bring your own.

* [symfony/http-foundation](https://symfony.com/components/HttpFoundation)
* [slim/psr7](https://github.com/slimphp/Slim-Psr7)
* [nimbly/Capsule](https://github.com/nimbly/Capsule)
* [zendframework/zend-diactoros](https://github.com/zendframework/zend-diactoros)

## Router

### Defining routes

There are convenience methods for all major HTTP verbs (get, post, put, patch, and delete).

```php
$router->get('/fruits', 'FruitsController@all');
$router->patch('/fruits/{id}', 'FruitsController@update');
```
A route can respond to any number of HTTP verbs.

```php
$router->add(['get', 'post'], '/fruits', 'FruitsController@create');
```
### Router paths

Paths can be static or contain place holders.

```php
// This route is static
$router->get('/books/new', 'BooksController@getNewBooks');

// This route needs an ISBN in the path.
$router->get('/books/{isbn}', 'BooksController@getByIsbn');
```

### Router path patterns

Your path place holders can also enforce a specific regular expression pattern when being matched.

```php
RouterAbstract::setPattern('isbn', "\d{9}[\d|X]");
$router->get('/books/{id:isbn}', 'BooksController@getByIsbn');
```

Limber has several predefined path patterns you can use (or create you own!):

* ```alpha``` Alphabetic characters only (A-Z), of any length
* ```int``` Integer number of any length
* ```alphanumeric``` Any combination of number or alphabetic character
* ```uuid``` A Universally Unique Identifier
* ```hex``` A hexidecimal value, of any length

### Router actions

Router actions may either be a ```\callable``` or a string in the format **ClassName@Method**.

```php
// Closure based actions
$router->get("/books", function(ServerRequestInterface $request): ResponseInterface {

	return new Response(200, "Oh hai!");

});

// String references to ClassName@Method
$router->patch("/books/{isbn}", "App\Controllers\BooksController@update");
```

When a Request is dispatched to the Route action, Limber will always pass the RequestInterface instance in as the first parameter. Any path parameters defined in the route will be passed into the action as well, in the order they appear in the Route URI pattern.

```php
$router->get('/books/{isbn}/comments/{id}', 'BooksController@getByIsbn');

class BooksController
{
	public function getByIsbn(ServerRequestInterface $request, string $isbn, string $id): ResponseInterface
	{
		....
	}
}
```

### Route groups
You can group routes together using the ```group``` method and passing in parameters that you want applied to all routes within that group.

* ```middleware``` &lt;array, string&gt; *or* &lt;array, MiddlewareLayerInterface&gt; An array of all middleware classes (fullname space) or actual instances of middleware.
* ```prefix``` &lt;string&gt; A string prepended to all URIs when matching the request.
* ```namespace``` &lt;string&gt; A string prepended to all string based actions before instantiating a new controller.
* ```hostname``` &lt;string&gt; A host name to be matched against.

```php
$router->group([
	'hostname' => 'sub.domain.com',
	'middleware' => [
		FooMiddleware::class,
		BarMiddleware::class
	],
	'namespace' => 'App\Sub.Domain\Controllers',
	'prefix' => 'v1'
], function($router){

	$router->get('books/{isbn}', 'BooksController@getByIsbn');
	$router->post('books', 'BooksController@create');

});
```

Groups can be nested and will inherit their parent group's settings unless the setting is overridden. Middleware settings however are merged in with their parent's settings.

```php
$router->group([
	'hostname' => 'sub.domain.com',
	'middleware' => [
		FooMiddleware::class,
		BarMiddleware::class
	],
	'namespace' => 'App\Sub.Domain\Controllers',
	'prefix' => 'v1'
], function($router){

	$router->get('books/{isbn}', 'BooksController@getByIsbn');
	$router->post('books', 'BooksController@create');

	// This group will inherit all group settings from the parent group
	// and will merge in an additional middleware (AdminMiddleware).
	$router->group([
		'middleware' => [
			AdminMiddleware::class
		]
	], function($router) {

		...

	});

});
```
### Loading routes from cache

When instantiating a ```Router```, you can pass in array of ```Route``` instances that will be loaded directly into the router.

This allows you to load your routes from disk or memory and inject directly into the router if you choose.

```php
// Load from disk
$routes = require __DIR__ . "/cache/routes.php";
$router = new Router($routes);

// Load from a cache
$routes = Cache::getItem("Limber\Routes");
$router = new Router($routes);
```

## Middleware
Limber uses a Before &amp; After middleware approach.

Middleware instances must implement the ```MiddlewareLayerInterface```.

```php
class FooMiddleware implements MiddlewareLayerInterface
{
	public function handle(ServerRequestInterface $request, callable $next): ResponseInterface
	{
		// Add a custom header to the request before sending to route handler
		$request = $request->withAddedHeader('X-Foo', 'Bar');

		// Pass request off to next middleware layer
		$response = $next($request);

		// Add a custom header to the response before sending back to client
		return $response->withAddedHeader('X-Custom-Header', 'Foo');
	}
}
```

### Middleware as Closures

Limber also supports any ```\callable``` as a middleware.

```php
$application->addMiddleware(function(ServerRequestInterface $request, callable $next): ResponseInterface {

	Log::info("Received request!");

	return $next($request);

});
```

### Global middleware

Global middleware is registered on the Application instance using the ```setMiddleware``` method.

Middleware is applied in the order they are registered.

```php
$application = new Limber\Application($router);

$application->setMiddleware([
	FooMiddleware::class,
	BarMiddleware::class
]);
```
You may optionally register a middleware layer one at a time by using the ```addMiddleware``` method.

```php
$application = new Limber\Application($router);

$application->addMiddleware(FooMiddleware::class);
$application->addMiddleware(BarMiddleware::class);
```

### Route middleware

Route middleware can be applied per route or per route group.

```php

// Middleware applied to single route
$route->get('/books/{id:isbn}', 'BooksController@getByIsbn')->setMiddleware([
	FooMiddleware::class
]);

// Middleware applied to entire group
$route->group([
	'middleware' => [
		FooMiddleware::class,
		BarMiddleware::class
	]
], function($router){

	...

});
```