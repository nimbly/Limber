# Limber

[![Latest Stable Version](https://img.shields.io/packagist/v/nimbly/limber.svg?style=flat-square)](https://packagist.org/packages/nimbly/Limber)
[![Build Status](https://img.shields.io/travis/com/nimbly/Limber.svg?style=flat-square)](https://app.travis-ci.com/github/nimbly/Limber)
[![Code Coverage](https://img.shields.io/coveralls/github/nimbly/Limber.svg?style=flat-square)](https://coveralls.io/github/nimbly/Limber)
[![License](https://img.shields.io/github/license/nimbly/Limber.svg?style=flat-square)](https://packagist.org/packages/nimbly/Limber)

A super minimal PSR-7, 15, and 11 compliant HTTP framework that doesn't get in your way.

Limber is intended for advanced users who are comfortable setting up their own framework and pulling in packages best suited for their particular use case.

## Limber includes
* A router
* PSR-7 HTTP message compliant
* PSR-11 container compliant
* PSR-15 middleware compliant
* A thin `Application` layer to tie everything together

## Requirements

* PHP 8.0+
* PSR-7 HTTP Message library

## Installation

```bash
composer require nimbly/limber
```

## Quick start

### Install PSR-7 library

Limber does not ship with a PSR-7 implementation which is required to receive HTTP requests and send back responses. Let's pull one into our project.

* [slim/psr7](https://github.com/slimphp/Slim-Psr7)
* [laminas/laminas-diactoros](https://github.com/laminas/laminas-diactoros)
* [guzzlehttp/psr7](https://github.com/guzzle/psr7)
* [nimbly/Capsule](https://github.com/nimbly/Capsule)

```bash
composer require nimbly/capsule
```

### Sample application

1. Create your entrypoint (or front controller), for example `index.php`, and start by creating a new `Router` instance and attaching your routes to it.

2. Once your routes have been defined, you can create the `Application` instance and pass the router in to it.

3. You can then `dispatch` requests through the application and receive a response back.

4. And finally, you can `send` a response back to the calling client.

```php
<?php

require __DIR__ . "/vendor/autoload.php";

// Create a Router instance and define a route.
$router = new Nimbly\Limber\Router\Router;
$router->get("/", fn() => new Capsule\Response(200, "Hello World!"));

// Create Application instance with router.
$application = new Nimbly\Limber\Application($router);

// Dispatch a PSR-7 ServerRequestInterface instance and get back a PSR-7 ResponseInterface instance
$response = $application->dispatch(
	Capsule\Factory\ServerRequestFactory::createFromGlobals()
);

// Send the ResponseInterface instance
$application->send($response);
```

## Advanced configuration

### A note on autowiring support

Limber will invoke your route handlers using reflection based autowiring. The `ServerRequestInterface` instance, URI path parameters defined in the route, and request attributes will be automatically resolved for you, without the need of a PSR-11 container.

However, any domain specific services and classes that are required in your handlers, should be defined in a PSR-11 container instance.

**NOTE:** *Union type autowiring is currently not supported.*

### Adding PSR-11 container support

Limber is able to autowire your request handlers and middleware with the aid of a PSR-11 container instance. However, Limber *does not ship* with a PSR-11 Container implementation, so you will need to bring your own if you require one. Here are some options:

* [PHP-DI](https://php-di.org/)
* [nimbly/Carton](https://github.com/nimbly/Carton)

Let's add container support to our application.

```bash
composer require nimbly/carton
```

And update our entry point by passing the container instance into the `Application` constructor.

```php
<?php

require __DIR__ . "/vendor/autoload.php";

// Create a Router instance and define a route.
$router = new Nimbly\Limber\Router\Router;
$router->get("/", fn() => new Capsule\Response(200, "Hello World!"));

// Create PSR-11 container instance and configure.
$container = new Container;
$container->set(
	Foo:class,
	fn(): Foo => new Foo(\getenv("FOO_NAME"))
);

// Create Application instance with router and container.
$application = new Nimbly\Limber\Application(
	router: $router,
	container: $container
);
```

### Middleware

Limber supports PSR-15 middleware. All middleware must implement `Psr\Http\Server\MiddlewareInterface`.

You can pass middleware as one or more of the following types:

* An instance of `MiddlewareInterface`
* A `class-string` that implements `MiddlewareInterface`
* A `class-string` that implements `MiddlewareInterface` as an index and an array of key=>value pairs as parameters to be used in dependency injection when autowiring.

Any `class-string` types will be auto wired using the `Container` instance (if any) for dependency injection.

If auto wiring fails, a `DependencyResolutionException` exception will be thrown.

```php
class SampleMiddleware implements MiddlewareInterface
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

Now let's add this middleware layer to the Limber application instance.

```php
<?php

require __DIR__ . "/vendor/autoload.php";

// Create a Router instance and define a route.
$router = new Nimbly\Limber\Router\Router;
$router->get("/", fn() => new Capsule\Response(200, "Hello World!"));

// Create PSR-11 container instance and configure.
$container = new Container;
$container->set(
	Foo:class,
	fn(): Foo => new Foo(\getenv("FOO_NAME"))
);

// Create Application instance with router and container.
$application = new Nimbly\Limber\Application(
	router: $router,
	container: $container,
	middleware: [
		App\Http\Middleware\SampleMiddlware::class
	]
);
```

### Exception handling

You can set a custom default exception handler that will process any exception thrown *within* the middleware chain.

The exception handler must implement `Nimbly\Limber\ExceptionHandlerInterface`.

**NOTE** Exceptions thrown *outside* of the middleware chain (e.g. during bootstrap process) will continue to bubble up unless caught elsewhere.

```php
namespace App\Http;

use Nimbly\Limber\ExceptionHandlerInterface;
use Nimbly\Limber\Exceptions\HttpException;

class ExceptionHandler implements ExceptionHandlerInterface
{
	public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
	{
		$status_code = $exception instanceof HttpException ? $exception->getCode() : 500;

		return new Response(
			$status_code,
			\json_encode([
				"error" => [
					"code" => $exception->getCode(),
					"message" => $exception->getMessage()
				]
			]),
			[
				"Content-Type" => "application/json"
			]
		);
	}
}
```

Now let's add the exception handler to the Limber application instance.

```php
$application = new Nimbly\Limber\Application(
	router: $router,
	container: $container,
	middleware: [
		App\Http\Middleware\FooMiddlware::class
	],
	exceptionHandler: new App\Http\ExceptionHandler
);
```

## Router

The `Router` builds and collects `Route` instances and provides helper methods to group `Routes` together sharing a common configuration (path prefix, namespace, middleware, etc).

### Defining routes

Create a `Router` instance and begin defining your routes. There are convenience methods for all major HTTP verbs (get, post, put, patch, and delete).

```php
$router = new Nimbly\Limber\Router\Router;
$router->get("/fruits", "FruitsHandler@all");
$router->post("/fruits", "FruitsHandler@create");
$router->patch("/fruits/{id}", "FruitsHandler@update");
$router->delete("/fruits/{id}", "FruitsHandler@delete");
```

A route can respond to any number of HTTP methods by using the `add` method and passing an array of methods as strings.

```php
$router->add(["get", "post"], "/fruits", "FruitsHandler@create");
```

### HEAD requests

By default, Limber will add a `HEAD` method to each `GET` route.

### Route paths

Paths can be static or contain named parameters. Named parameters will be injected into your
route handler if the handler also contains a parameter of the same name.

```php
$router->get("/books/{isbn}", "BooksHandler@findByIsbn");
```

In the following handler, both the `$request` and `$isbn` parameters will be injected automatically.

```php
class BooksHandler
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
$router->get("/books/{id:uuid}", "BooksHandler@get");
```

You can define your own patterns to match using the `Router::setPattern()` static method.

```php
Router::setPattern("isbn", "\d{9}[\d|X]");

$router = new Router;
$router->get("/books/{id:isbn}", "BooksHandler@getByIsbn");
```

### Route handlers

Route handlers may either be a `callable` or a string in the format **Fully\Qualified\Namespace\ClassName@Method** (for example `App\Handlers\v1\BooksHandler@create`).

Route handlers *must* return a `ResponseInterface` instance.

Limber uses reflection based autowiring to automatically resolve your route handlers including constructor and function/method parameters. The `ServerRequestInterface` instance, path parameters, and any attributes attached to the  `ServerRequestInterface` instance will be resolved and injected for you. This applies for both closure based handlers as well as **Class@Method** based handlers.

You may also optionally supply a PSR-11 compliant `ContainerInterface` instance to aid in route handler parameter resolution. By doing this, you can easily have your application specific dependencies resolved and injected into your handlers by Limber. See **PSR-11 Container support** section for more information.

```php
// Closure based handler
$router->get(
	"/books/{id:isbn}",
	function(ServerRequestInterface $request, string $id): ResponseInterface {
		$book = Books::find($id);

		if( empty($book) ){
			throw new NotFoundHttpException("Book not found.");
		}

		return new Response(200, \json_encode($book));
	}
);

// String references to ClassName@Method
$router->patch("/books/{id:isbn}", "App\Handlers\BooksHandler@update");

// If a ContainerInterface instance was assigned to the application and contains an InventoryService instance, it will be injected into this handler.
$router->post(
	"/books",
	function(ServerRequestInterface $request, InventoryService $inventoryService): ResponseInterface {
		$book = Book::make($request->getParsedBody());

		$inventoryService->add($book);

		return new Response(201, \json_encode($book));
	}
);
```

### Route configuration

You can configure individual routes to respond to a specific scheme, a specific hostname, process additional middleware, or pass along attributes to the `ServerRequestInterface` instance.

#### Scheme

```php
$router->post(
	path: "books",
	handler: "\App\Http\Handlers\BooksHandler@create",
	scheme: "https"
);
```

### Route specific middleware

```php
$router->post(
	path: "books",
	handler: "\App\Http\Handlers\BooksHandler@create",
	middleware: [new FooMiddleware]
);
```

#### Hostname

```php
$router->post(
	path: "books",
	handler: "\App\Http\Handlers\BooksHandler@create",
	hostnames: ["example.org"]
);
```

#### Attributes

```php
$router->post(
	path: "books",
	handler: "\App\Http\Handlers\BooksHandler@create",
	attributes: [
		"Attribute1" => "Value1"
	]
);
```

### Route groups

You can group routes together using the `group` method and all routes contained will inherit the configuration you have defined.

* `scheme` *string* The HTTP scheme (`http` or `https`) to match against. A `null` value will match against any value.
* `middleware` *array&lt;string&gt;* or *array&lt;MiddlewareInterface&gt;* or *array&lt;callable&gt;* An array of all middleware classes (fully qualified namespace) or actual instances of middleware.
* `prefix` *string* A string prepended to all URIs when matching the request.
* `namespace` *string* A string prepended to all string based handlers before instantiating a new class.
* `hostnames` *array&lt;string&gt;* An array of hostnames to be matched against.
* `attributes` *array&lt;string,mixed&gt;* An array of key=>value pairs representing attributes that will be attached to the `ServerRequestInterface` instance if the route matches.
* `routes` *callable* A callable that accepts the `Router` instance where you can add additional routes within the group.

```php
$router->group(
	hostnames: ["sub.domain.com"],
	middleware: [
		FooMiddleware::class,
		BarMiddleware::class
	],
	namespace: "\App\Sub.Domain\Handlers",
	prefix: "v1",
	routes: function(Router $router): void {
		$router->get("books/{isbn}", "BooksHandler@getByIsbn");
		$router->post("books", "BooksHandler@create");
	}
);
```

Groups can be nested and will inherit their parent group's settings unless the setting is overridden. Middleware settings however are *merged* with their parent's settings.

```php
$router->group(
	hostnames: ["sub.domain.com"],
	middleware: [
		FooMiddleware::class,
		BarMiddleware::class
	],
	namespace: "\App\Sub.Domain\Handlers",
	prefix: "v1",
	routes: function(Router $router): void {

		$router->get("books/{isbn}", "BooksHandler@getByIsbn");
		$router->post("books", "BooksHandler@create");

		// This group will inherit all group settings from the parent group, override
		// the namespace property, and will merge in an additional middleware (AdminMiddleware).
		$router->group(
			namespace: "\App\Sub.Domain\Handlers\Admin",
			middleware: [
				AdminMiddleware::class
			],
			routes: function(Router $router): void {
				$route->delete("books/{isbn}", "BooksHandler@deleteBook");
			}
		);
	}
);
```

## Using with React/Http

Because Limber is PSR-7 compliant, it works very well with [react/http](https://github.com/reactphp/http) to create a standalone HTTP service without the need for an additional HTTP server (nginx, Apache, etc) - great for containerizing your service with minimal dependencies.

### Install React/Http
```bash
composer install react/http
```

### Create entry point

Create a file called `main.php` (or whatever you want) to be the container's command/entry point.

```php
<?php

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Nimbly\Capsule\Response;

// Create the router and some routes.
$router = new Nimbly\Limber\Router;
$router->get("/", function(ServerRequestInterface $request): ResponseInterface {
	return new Response(
		"Hello world!"
	);
});

// Create the Limber Application instance.
$application = new Nimbly\Limber\Application($router);

// Create the HTTP server to handle incoming HTTP requests with your Limber Application instance.
$httpServer = new React\Http\HttpServer(
	function(ServerRequestInterface $request) use ($application): ResponseInterface {
		return $application->dispatch($request);
	}
);

// Listen on port 8000.
$httpServer->listen(
	new React\Socket\SocketServer("0.0.0.0:8000");
);
```

### Create Dockerfile

Create a `Dockerfile` in the root of your application.

We'll extend from the official PHP 8 docker image and add some useful tools like `composer`, a better event loop library from PECL, and install support for process control (`pcntl`).

Obviously, edit this file to match your specific needs.

```docker
FROM php:8.0-cli

RUN apt-get update && apt-get upgrade --yes
RUN curl --silent --show-error https://getcomposer.org/installer | php && \
	mv composer.phar /usr/bin/composer
RUN mkdir -p /usr/src/php/ext && curl --silent https://pecl.php.net/get/ev-1.1.5.tgz | tar xvzf - -C /usr/src/php/ext

# Add other PHP modules
RUN docker-php-ext-install pcntl ev-1.1.5

WORKDIR /opt/service
ADD . .
RUN composer install --no-dev
CMD [ "php", "main.php" ]
```

### Build docker image

```bash
docker image build -t my-service:latest .
```

### Run as container
```bash
docker container run -p 8000:8000 --env-file=.env my-service:latest
```