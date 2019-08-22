# Limber

A flexible minimal HTTP framework.

## Features
* 3 different routing engines to chose from
* Middleware (Before &amp; After)
* Bring Your Own PSR-7

## Installation

```bash
composer require nimbly/Limber
```

## Quick start

```php
$application = new Limber\Application(
	$routes
);

$response = $application->dispatch(
	$serverRequest
);

$application->send($response);
```

## PSR-7
Limber does not ship with a PSR-7 (HTTP Message) implementation, so you will need to bring your own.

* symfony/http-foundation
* slim/psr7
* nimbly/Capsule
* zendframework/zend-diactoros

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
* ```namespace``` &lt;string&gt; A string prepended to all string based actions before instantiating a new controoler.
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

### Global middleware

Global middleware is applied on the Application instance using the ```setMiddleware``` method.

```php
$application = new Limber\Application($router);

$application->setMiddleware([
	FooMiddleware::class,
	BarMiddleware::class
]);
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