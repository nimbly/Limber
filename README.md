# Limber

[![Latest Stable Version](https://img.shields.io/packagist/v/nimbly/limber.svg?style=flat-square)](https://packagist.org/packages/nimbly/Limber)
[![Build Status](https://img.shields.io/travis/nimbly/Limber.svg?style=flat-square)](https://travis-ci.com/nimbly/Limber)
[![Code Coverage](https://img.shields.io/coveralls/github/nimbly/Limber.svg?style=flat-square)](https://coveralls.io/github/nimbly/Limber)
[![License](https://img.shields.io/github/license/nimbly/Limber.svg?style=flat-square)](https://packagist.org/packages/nimbly/Limber)

A super minimal PSR-7 and PSR-15 compliant HTTP framework that doesn"t get in your way.

Limber is intended for advanced users who are comfortable setting up their own framework and pulling in packages best suited for their particular use case.

## Limber includes
* A router
* PSR-7 HTTP message compliant
* PSR-11 container compliant
* PSR-15 middleware compliant
* A thin `Application` layer to tie everything together

## Installation

```bash
composer require nimbly/limber
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

Limber *does not ship* with a PSR-7 HTTP Message implementation so you will need to bring your own. Here are some options:

* [slim/psr7](https://github.com/slimphp/Slim-Psr7)
* [laminas/laminas-diactoros](https://github.com/laminas/laminas-diactoros)
* [guzzlehttp/psr7](https://github.com/guzzle/psr7)
* [nimbly/Capsule](https://github.com/nimbly/Capsule)

## PSR-11

Limber *does not ship* with a PSR-11 Container implementation, so you will need to bring your own if you need one. Here are some options:

* [PHP-DI](http://php-di.org/)
* [nimbly/Carton](https://github.com/nimbly/Carton)

## Router

The `Router` builds and collects `Route` instances and provides helper methods to group `Routes` together sharing a common configuration (prefix, namespace, middleware, etc).

### Defining routes

Create a `Router` instance and begin defining your routes. There are convenience methods for all major HTTP verbs (get, post, put, patch, and delete).

```php
$router = new Limber\Router\Router;
$router->get("/fruits", "FruitsController@all");
$router->post("/fruits", "FruitsController@create");
$router->patch("/fruits/{id}", "FruitsController@update");
$router->delete("/fruits/{id}", "FruitsContoller@delete");
```

A route can respond to any number of HTTP verbs.

```php
$router->add(["get", "post"], "/fruits", "FruitsController@create");
```

### HEAD requests

By default, Limber will add a `HEAD` method to each `GET` route.

### Route paths

Paths can be static or contain named parameters. Named parameters will be injected into your
route handler if the handler also contains a parameter of the same name.

```php
$router->get("/books/{isbn}", "BooksController@findByIsbn");
```

In the following handler, both the `$request` and `$isbn` parameters will be injected automatically.

```php
class BooksController
{
    public function getByIsbn(ServerRequestInterface $request, string $isbn): ResponseInterface
    {
        $book = BookModel::findByIsbn($isbn);

        if( empty($book) ){
            throw new NotFoundHttpException("ISBN not found.");
        }

        return new JsonResponse(
            200,
            $book->toArray()
        );
    }
}
```

### Route path patterns

Your named parameters can also enforce a specific regular expression pattern when being matched - just add the pattern after the placeholder name with a colon.

Limber has several predefined path patterns you can use:

* `alpha` Alphabetic characters only (A-Z), of any length
* `int` Integer number of any length
* `alphanumeric` Any combination of number or alphabetic character
* `uuid` A Universally Unique Identifier
* `hex` A hexidecimal value, of any length

```php
// Get a book by its ID and match the ID to a UUID.
$router->get("/books/{id:uuid}", "BooksController@get");
```

You can define your own patterns to match using the `Router::setPattern()` static method.

```php
Router::setPattern("isbn", "\d{9}[\d|X]");
$router->get("/books/{id:isbn}", "BooksController@getByIsbn");
```

### Route actions

Route actions (or handlers) may either be a `\callable` or a string in the format **Fully\Qualified\Namespace\ClassName@Method** (for example `App\Handlers\v1\BookHandler@create`).

Route actions *must* return a `ResponseInterface` instance.

Limber uses reflection based autowiring to automatically resolve your route action"s parameters - including the `ServerRequestInterface` instance
and any path parameters. This applies for both closure based handlers as well as **Class@Method** based handlers.

You may also optionally supply a PSR-11 compliant `ContainerInterface` instance to aid in route handler resolution. By doing this, you can
easily have your application specific dependencies resolved and injected into your handlers by Limber. See **PSR-11 Container support** section
for more information.

```php
// Closure based actions
$router->get("/books/{id:isbn}", function(ServerRequestInterface $request, string $id): ResponseInterface {

	$book = Books::find($id);

	if( empty($book) ){
		throw new NotFoundHttpException("Book not found.");
	}

	return new Response(
		\json_encode($book)
	);

});

// String references to ClassName@Method
$router->patch("/books/{id:isbn}", "App\Controllers\BooksController@update");

// If a ContainerInterface instance was assigned to the application and contains an InventoryService instance, it will be injected into this handler.
$router->post("/books", function(ServerRequestInterface $request, InventoryService $inventoryService): ResponseInterface {

	$book = Book::make($request->getAll());

	$inventoryService->add($book);

	return new Response(
		\json_encode($book)
	);
});
```

### Route configuration

You can configure individual routes to respond to a specific scheme, a specific hostname, process additional middleware, or pass along attributes to the `ServerRequestInterface` instance.

#### Scheme

```php
$router->post("books", "BooksHandler@create")->setScheme("https");
```

#### Middleware

```php
$router->post("books", "BooksHandler@create")->setMiddleware([new FooMiddleware]);
```

#### Hostname

```php
$router->post("books", "BooksHandler@create")->setHostname("example.org");
```

#### Attributes

```php
$router->post("books", "BooksHandler@create")->setAttributes(["Attribute" => "Value"]);
```

### Route groups

You can group routes together using the `group` method and passing in array of configurations that you want applied to all routes within that group.

* `scheme` *string* The HTTP scheme (http or https) to match against.
* `middleware` *array&lt;string&gt;* or *array&lt;MiddlewareInterface&gt;* or *array&lt;callable&gt;* An array of all middleware classes (fullname space) or actual instances of middleware.
* `prefix` *string* A string prepended to all URIs when matching the request.
* `namespace` *string* A string prepended to all string based actions before instantiating a new class.
* `hostname` *string* A host name to be matched against.
* `attributes` *array&lt;string,mixed&gt;* An array of key=>value pairs representing attributes that will be attached to the `ServerRequestInterface` instance if the route matches.

```php
$router->group([
	"hostname" => "sub.domain.com",
	"middleware" => [
		FooMiddleware::class,
		BarMiddleware::class
	],
	"namespace" => "App\Sub.Domain\Controllers",
	"prefix" => "v1"
], function($router){

	$router->get("books/{isbn}", "BooksController@getByIsbn");
	$router->post("books", "BooksController@create");

});
```

Groups can be nested and will inherit their parent group"s settings unless the setting is overridden. Middleware settings however are *merged* with their parent"s settings.

```php
$router->group([
	"hostname" => "sub.domain.com",
	"middleware" => [
		FooMiddleware::class,
		BarMiddleware::class
	],
	"namespace" => "App\Sub.Domain\Controllers",
	"prefix" => "v1"
], function($router){

	$router->get("books/{isbn}", "BooksController@getByIsbn");
	$router->post("books", "BooksController@create");

	// This group will inherit all group settings from the parent group
	// and will merge in an additional middleware (AdminMiddleware).
	$router->group([
		"middleware" => [
			AdminMiddleware::class
		]
	], function($router) {

		...

	});

});
```

### Loading routes from cache

When instantiating a `Router`, you can pass in an array of `Route` instances that will be loaded directly into the router.

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
$route->get("/books/{id:isbn}", "BooksController@getByIsbn")->setMiddleware([
	FooMiddleware::class
]);

// Middleware applied to entire group
$route->group([
	"middleware" => [
		FooMiddleware::class,
		BarMiddleware::class
	]
], function($router){

	...

});
```

## Middleware

Limber uses PSR-15 middleware. All middleware must implement `Psr\Http\Server\MiddlewareInterface`.

```php
class FooMiddleware implements MiddlewareInterface
{
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		// Add a custom header to the request before sending to route handler
		$request = $request->withAddedHeader("X-Foo", "Bar");

		$response = $handler->handle($request);

		// Add a custom header to the response before sending back to client
		return $response->withAddedHeader("X-Custom-Header", "Foo");
	}
}
```

### Middleware as Closures

Limber supports any `\callable` as a middleware as long as the `\callable` signature matches `Psr\Http\Server\MiddlewareInterface`.

```php
$application->addMiddleware(function(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {

	Log::info("Received request!");
	return $handler->handle($request);

});
```

## Application

### Instantiating

An `Application` instance requires only a `Router` instance.

```php
$application = new Application($router);
```

### Setting global middleware

You can set global middleware directly on the `Application` instance. Global middleware is applied to *all* requests and processed in the order they are registered.

```php
$application->setMiddleware([
	new GlobalMiddleware1,
	new GlobalMiddleware2,
	new GlobalMiddleware3
]);
```

Or you can add global middleware individually.

```php
$application->addMiddleware(new GlobalMiddleware1);
$application->addMiddleware(new GlobalMiddleware2);
$application->addMiddleware(new GlobalMiddleware3);
```

You can pass middleware as one or more of the following types:

* An instance of `MiddlewareInterface`
* A `callable`
* A `class-string`
* A `class-string` as an index and an array of key=>value pairs as parameters to be used in dependency injection when auto wiring.

Any `class-string` types will be auto wired using the `Container` instance (if any) for dependency injection.

If auto wiring fails, a `DependencyResolutionException` exception will be thrown.

```php
$application->setMiddleware([
    new FooMiddleware,
    FooMiddleware::class,
    FooMiddleware::class => ["param1" => "Foo"],
    function(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        return $handler->handle($request);
    }
]);
```

### Exception handling

You can set a custom exception handler that will process any exception thrown *within* the middleware chain.

The exception handler must be a `\callable` and accept an instance of `Throwable` as its only argument and return an instance of `ResponseInterface`.

```php
$application->setExceptionHandler(function(Throwable $exception): ResponseInterface {

	return new Response(
		\render("errors/" . $exception->getHttpStatus()),
		$exception->getHttpStatus(),
		[
			"Content-Type" => "text/html"
		]
	);

});
```

**NOTE** Exceptions thrown *outside* of the middleware chain will continue to bubble up unless caught elsewhere.

### Autowiring support

Limber will invoke your route handlers using reflection based autowiring. The `ServerRequestInterface` instance and any URI path parameters will be automatically resolved for you.

### PSR-11 Container support

Optionally, you can provide the `Application` instance a PSR-11 compatible `ContainerInterface` instance to be used when invoking route handlers or instantiating class based
handlers to inject your application specific dependencies where needed.

```php
$container = new Psr11\Library\Container;
$container->register(
	[
		// register your service providers
	]
);

$application->setContainer($container);
```

### Dependency injection

Limber can call any `callable` for you with the added benefit of having dependencies resolved and injected for you.

```php
$callable = function(DependencyInContainer $dep1): Foo {
    return $dep1->getFoo();
};

$foo = $application->call($callable);
```

You can pass in user arguments as a second parameter to `call`.

```php
$callable = function(DependencyInContainer $dep1, string $name): Foo {
    return $dep1->getFoo($name);
};

$foo = $application->call($callable, ["name" => $name]);
```

If the dependency cannot be resolved, Limber will attempt to `make` one for you. If it cannot `make`, a `DependencyResolutionException` will be thrown.

### Handling a Request

To handle an incoming request, simply `dispatch` a PSR-7 `ServerRequestInterface` instance and capture the response.

```php
$response = $application->dispatch(
	ServerRequest::createFromGlobals()
);
```

### Sending the Response

To send a PSR-7 `ResponseInterface` instance, call the `send` method with the `ResponseInteface` instance.

```php
$application->send($response);
```

## Using with React/Http

Because Limber is PSR-7 compliant, it works very well with [react/http](https://github.com/reactphp/http) to create a standalone HTTP service without the need for an additional HTTP server (nginx, Apache, etc) - great for containerizing your service with minimal dependencies.

### Install React/Http
```bash
composer install react/http
```

### Create entry point

Create a file called `main.php` (or whatever you want) to be the container"s command/entry point.

```php
<?php

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

// Create the router and some routes.
$router = new Limber\Router;
$router->get("/", function(ServerRequestInterface $request): ResponseInterface {
	return new Response(
		"Hello world!"
	);
});

// Create the Limber Application instance.
$application = new Limber\Application($router);

// Create the React EventLoop instance.
$eventLoop = React\EventLoop\Factory::create();

// Create the HTTP server to handle incoming HTTP requests with your Limber Application instance.
$httpServer = new React\Http\Server(
    $eventLoop,
    function(ServerRequestInterface $request) use ($application): ResponseInterface {

	    return $application->dispatch($request);

    }
);

// Listen on port 8000.
$httpServer->listen(
	new React\Socket\Server("0.0.0.0:8000", $eventLoop);
);

// Run the server!
$eventLoop->run();
```

### Create Dockerfile

```bash
FROM php:7.4-cli

WORKDIR /opt/service

COPY . .

CMD ["php", "/opt/service/main.php"]
```

### Build docker image

```bash
docker image build -t my-service:latest .
```

### Run as container
```bash
docker container run -p 8000:8000 --env-file=.env my-service:latest
```