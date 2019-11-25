# Limber

[![Latest Stable Version](https://img.shields.io/packagist/v/nimbly/Limber.svg?style=flat-square)](https://packagist.org/packages/nimbly/Limber)
[![Build Status](https://img.shields.io/travis/nimbly/Limber.svg?style=flat-square)](https://travis-ci.org/nimbly/Limber)
[![Code Coverage](https://img.shields.io/coveralls/github/nimbly/Limber.svg?style=flat-square)](https://coveralls.io/github/nimbly/Limber)
[![License](https://img.shields.io/github/license/nimbly/Limber.svg?style=flat-square)](https://packagist.org/packages/nimbly/Limber)

A super minimal HTTP framework that doesn't get in your way.

Limber is intended for advanced users who are comfortable setting up their own framework and pulling in packages best suited for their particular use case.

## Limber includes
* A router
* PSR-15 middleware support
* A thin Application layer to:
	* Attach router
	* Add global middleware
	* Add exception handler
	* Dispatch PSR-7 ServerRequest
	* Send PSR-7 Response

## Installation

```bash
composer require nimbly/Limber
```

## Quick start

```php
// Create a Router instance and define routes
$router = new Limber\Router\Router;
$router->get("/", function(ServerRequestInterface $request): ResponseInterface {

	return new Response(
		\render("home/index")
	);

});

// Create Application instance with the Router
$application = new Limber\Application(
	$router
);

// Dispatch a PSR-7 ServerRequestInterface instance and get back a PSR-7 ResponseInterface instance
$response = $application->dispatch(
	Some\Psr7\ServerRequest::createFromGlobals()
);

// Send the ResponseInterface instance
$application->send($response);
```

## PSR-7

Limber *does not ship* with a PSR-7 (HTTP Message) implementation so you will need to bring your own.

* [symfony/http-foundation](https://symfony.com/components/HttpFoundation)
* [slim/psr7](https://github.com/slimphp/Slim-Psr7)
* [nimbly/Capsule](https://github.com/nimbly/Capsule)
* [zendframework/zend-diactoros](https://github.com/zendframework/zend-diactoros)

## Router

### Defining routes

Create a ```Router``` instance and begin defining your routes. There are convenience methods for all major HTTP verbs (get, post, put, patch, and delete).

```php
$router = new Limber\Router\Router;
$router->get('/fruits', 'FruitsController@all');
$router->post('/fruits', 'FruitsController@create');
$router->patch('/fruits/{id}', 'FruitsController@update');
$router->delete('/fruits/{id}', 'FruitsContoller@delete');
```

A route can respond to any number of HTTP verbs.

```php
$router->add(['get', 'post'], '/fruits', 'FruitsController@create');
```

### HEAD requests

By default, Limber will add a ```HEAD``` method to each ```GET``` route.

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

Just add the pattern after the placeholder name with a colon.

Limber has several predefined path patterns you can use:

* ```alpha``` Alphabetic characters only (A-Z), of any length
* ```int``` Integer number of any length
* ```alphanumeric``` Any combination of number or alphabetic character
* ```uuid``` A Universally Unique Identifier
* ```hex``` A hexidecimal value, of any length

```php
// Get a book by its ID and match the ID to a UUID.
$router->get('/books/{id:uuid}', 'BooksController@get');
```

You can define your own patterns to match using ```Router::setPattern()``` static method.

```php
Router::setPattern('isbn', '\d{9}[\d|X]');
$router->get('/books/{id:isbn}', 'BooksController@getByIsbn');
```

### Router actions

Router actions may either be a ```\callable``` or a string in the format **Fully\Qualified\Namespace\ClassName@Method**.

When a Request is dispatched to the Route action, Limber will always pass the ```ServerRequestInterface``` instance in as the *first* parameter. Any path parameters defined in the route will be passed into the action as well, in the order they appear in the Route URI pattern.

Route actions *must* return a ```ResponseInterface``` instance.

```php
// Closure based actions
$router->get("/books", function(ServerRequestInterface $request): ResponseInterface {

	return new Response(
		\json_encode(Books::all())
	);

});

// String references to ClassName@Method
$router->patch("/books/{id:isbn}", "App\Controllers\BooksController@update");
```

### Route groups
You can group routes together using the ```group``` method and passing in parameters that you want applied to all routes within that group.

* ```scheme``` *string* The HTTP scheme (http or https) to match against.
* ```middleware``` *array&lt;string&gt;* or *array&lt;MiddlewareInterface&gt;* or *array&lt;callable&gt;* An array of all middleware classes (fullname space) or actual instances of middleware.
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

Groups can be nested and will inherit their parent group's settings unless the setting is overridden. Middleware settings however are *merged* with their parent's settings.

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

When instantiating a ```Router```, you can pass in an array of ```Route``` instances that will be loaded directly into the router.

This allows you to load your routes from disk or memory and inject directly into the router if you choose.

```php
// Load from disk
$routes = require __DIR__ . "/cache/routes.php";
$router = new Router($routes);

// Load from a cache
$routes = Cache::getItem("Limber\Routes");
$router = new Router($routes);
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

## Middleware

Limber uses PSR-15 middleware. All middleware must implement ```Psr\Http\Server\MiddlewareInterface```.

```php
class FooMiddleware implements MiddlewareInterface
{
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		// Add a custom header to the request before sending to route handler
		$request = $request->withAddedHeader('X-Foo', 'Bar');

		$response = $handler->handle($request);

		// Add a custom header to the response before sending back to client
		return $response->withAddedHeader('X-Custom-Header', 'Foo');
	}
}
```

### Middleware as Closures

Limber supports any ```\callable``` as a middleware as long as the ```\callable``` signature matches ```Psr\Http\Server\MiddlewareInterface```.

```php
$application->addMiddleware(function(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {

	Log::info("Received request!");
	return $handler->handle($request);

});
```

## Application

### Instantiating

An ```Application``` instance requires only a ```Router``` instance.

```php
$application = new Application($router);
```

### Setting global middleware

You can set global middleware directly on the ```Application``` instance. Middleware is applied in the order they are registered.

```php
$application->setMiddleware([
	new GlobalMiddleware1,
	new GlobalMiddleware2,
	new GlobalMiddleware3
]);
```

Or you can add middleware individually.

```php
$application->addMiddleware(new GlobalMiddleware1);
$application->addMiddleware(new GlobalMiddleware2);
$application->addMiddleware(new GlobalMiddleware3);
```

### Exception handling

You can set a custom exception handler that will process any exception thrown *within* the middleware chain.

The exception handler must be a ```\callable``` and accept an instance of ```Throwable``` as its only argument and return an instance of ```ResponseInterface```.

```php
$application->setExceptionHandler(function(Throwable $exception): ResponseInterface {

	return new Response(
		\render("errors/" . $exception->getHttpStatus()),
		$exception->getHttpStatus(),
		[
			'Content-Type' => 'text/html'
		]
	);

});
```

**NOTE** Exceptions thrown *outside* of the middleware chain will continue to bubble up unless caught elsewhere.

### Handling a Request

To handle an incoming request, simply ```dispatch``` a PSR-7 ```ServerRequestInterface``` instance and capture the response.

```php
$response = $application->dispatch(
	new ServerRequest
);
```

### Sending the Response

To send a PSR-7 ```ResponseInterface``` instance, call the ```send``` method.

```php
$application->send($response);
```