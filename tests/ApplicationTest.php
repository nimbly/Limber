<?php

namespace Limber\Tests;

use Capsule\Response;
use Capsule\ResponseStatus;
use Capsule\ServerRequest;
use Limber\Application;
use Limber\Exceptions\BadRequestHttpException;
use Limber\Exceptions\DispatchException;
use Limber\Exceptions\MethodNotAllowedHttpException;
use Limber\Exceptions\NotFoundHttpException;
use Limber\Middleware\MiddlewareManager;
use Limber\Router\Router;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ApplicationTest extends TestCase
{
	public function test_constructor()
	{
		$router = new Router;

		$application = new Application($router);

		$reflection = new \ReflectionClass($application);

		$property = $reflection->getProperty('router');
		$property->setAccessible(true);

		$this->assertSame(
			$router,
			$property->getValue($application)
		);
	}

	public function test_set_middleware()
	{
		$application = new Application(
			new Router
		);

		$application->setMiddleware([
			"\App\Middleware\MyMiddleware"
		]);

		$reflection = new \ReflectionClass($application);

		$property = $reflection->getProperty('middleware');
		$property->setAccessible(true);

		$this->assertEquals(
			[
				"\App\Middleware\MyMiddleware"
			],
			$property->getValue($application)
		);
	}

	public function test_add_middleware()
	{
		$application = new Application(
			new Router
		);

		$application->setMiddleware([
			"\App\Middleware\MyMiddleware"
		]);

		$application->addMiddleware("\App\Middleware\MyOtherMiddleware");

		$reflection = new \ReflectionClass($application);
		$property = $reflection->getProperty('middleware');
		$property->setAccessible(true);

		$this->assertEquals(
			[
				"\App\Middleware\MyMiddleware",
				"\App\Middleware\MyOtherMiddleware"
			],
			$property->getValue($application)
		);
	}

	public function test_set_exception_handler()
	{
		$application = new Application(
			new Router
		);

		$handler = function(\Throwable $exception) {};

		$application->setExceptionHandler($handler);

		$reflection = new \ReflectionClass($application);
		$property = $reflection->getProperty('exceptionHandler');
		$property->setAccessible(true);

		$this->assertSame($handler, $property->getValue($application));
	}

	public function test_resolve_route_found()
	{
		$router = new Router;

		$route = $router->get("/books", function(ServerRequestInterface $request){
			return new Response(
				ResponseStatus::OK,
				"OK"
			);
		});

		$application = new Application($router);

		$reflection = new \ReflectionClass($application);
		$method = $reflection->getMethod('resolveRoute');
		$method->setAccessible(true);

		$request = ServerRequest::create("get", "http://example.org/books", null, [], [], [], []);

		$resolvedRoute = $method->invokeArgs($application, [$request]);

		$this->assertSame($route, $resolvedRoute);
	}

	public function test_resolve_route_not_found()
	{
		$router = new Router;

		$router->get("/books", function(ServerRequestInterface $request){
			return new Response(
				ResponseStatus::OK,
				"OK"
			);
		});

		$application = new Application($router);

		$reflection = new \ReflectionClass($application);
		$method = $reflection->getMethod('resolveRoute');
		$method->setAccessible(true);

		$request = ServerRequest::create("get", "http://example.org/authors", null, [], [], [], []);

		$this->expectException(NotFoundHttpException::class);
		$method->invokeArgs($application, [$request]);
	}

	public function test_resolve_route_method_not_allowed()
	{
		$router = new Router;

		$router->get("/books", function(ServerRequestInterface $request){
			return new Response(
				ResponseStatus::OK,
				"OK"
			);
		});

		$application = new Application($router);

		$reflection = new \ReflectionClass($application);
		$method = $reflection->getMethod('resolveRoute');
		$method->setAccessible(true);

		$request = ServerRequest::create("post", "http://example.org/books", null, [], [], [], []);

		$this->expectException(MethodNotAllowedHttpException::class);
		$method->invokeArgs($application, [$request]);
	}

	public function test_resolve_action_closure()
	{
		$router = new Router;
		$handler = function(ServerRequestInterface $request){
			return new Response(
				ResponseStatus::OK,
				"OK"
			);
		};
		$route = $router->get("/books", $handler);

		$application = new Application($router);

		$reflection = new \ReflectionClass($application);
		$method = $reflection->getMethod('resolveAction');
		$method->setAccessible(true);

		$action = $method->invokeArgs($application, [$route]);

		$this->assertSame(
			$handler,
			$action
		);
	}

	public function test_resolve_action_string()
	{
		$router = new Router;
		$route = $router->get("/books", self::class . "@test_resolve_action_string");

		$application = new Application($router);

		$reflection = new \ReflectionClass($application);
		$method = $reflection->getMethod('resolveAction');
		$method->setAccessible(true);

		$action = $method->invokeArgs($application, [$route]);

		$this->assertTrue(\is_callable($action));
	}

	public function test_resolve_action_with_unresolvable()
	{
		$router = new Router;
		$route = $router->get("/books", new \StdClass);

		$application = new Application($router);

		$reflection = new \ReflectionClass($application);
		$method = $reflection->getMethod('resolveAction');
		$method->setAccessible(true);

		$this->expectException(DispatchException::class);

		$action = $method->invokeArgs($application, [$route]);
	}

	public function test_handle_exception_with_exception_handler_set()
	{
		$application = new Application(new Router);
		$application->setExceptionHandler(function(\Throwable $exception): ResponseInterface {

			return new Response(
				$exception->getHttpStatus(),
				$exception->getMessage()
			);

		});

		$reflection = new \ReflectionClass($application);
		$method = $reflection->getMethod('handleException');
		$method->setAccessible(true);

		$request = ServerRequest::create("get", "http://example.org/authors", null, [], [], [], []);

		$response = $method->invokeArgs($application, [new NotFoundHttpException("Route not found")]);

		$this->assertEquals(ResponseStatus::NOT_FOUND, $response->getStatusCode());
		$this->assertEquals("Route not found", $response->getBody()->getContents());
	}

	public function test_handle_exception_with_no_exception_handler_set()
	{
		$application = new Application(new Router);

		$reflection = new \ReflectionClass($application);
		$method = $reflection->getMethod('handleException');
		$method->setAccessible(true);

		$request = ServerRequest::create("get", "http://example.org/authors", null, [], [], [], []);

		$this->expectException(NotFoundHttpException::class);

		$response = $method->invokeArgs($application, [new NotFoundHttpException("Route not found")]);
	}

	public function test_run_middleware()
	{
		$router = new Router;
		$route = $router->get("/books", function(ServerRequestInterface $request){
			return new Response(
				ResponseStatus::OK,
				"OK"
			);
		});

		$application = new Application($router);

		$reflection = new \ReflectionClass($application);
		$method = $reflection->getMethod('runMiddleware');
		$method->setAccessible(true);

		$request = ServerRequest::create("get", "http://example.org/authors", null, [], [], [], []);

		$response = $method->invokeArgs($application, [new MiddlewareManager, $request, $route]);

		$this->assertEquals(ResponseStatus::OK, $response->getStatusCode());
		$this->assertEquals("OK", $response->getBody()->getContents());
	}

	public function test_run_middleware_with_thrown_exception_and_exception_handler_set()
	{
		$router = new Router;
		$route = $router->get("/books", function(ServerRequestInterface $request){
			throw new BadRequestHttpException("Bad request");
		});

		$application = new Application($router);
		$application->setExceptionHandler(function(\Throwable $exception){

			return new Response(
				$exception->getHttpStatus(),
				$exception->getMessage()
			);

		});

		$reflection = new \ReflectionClass($application);
		$method = $reflection->getMethod('runMiddleware');
		$method->setAccessible(true);

		$request = ServerRequest::create("get", "http://example.org/authors", null, [], [], [], []);

		$response = $method->invokeArgs($application, [new MiddlewareManager, $request, $route]);

		$this->assertEquals(ResponseStatus::BAD_REQUEST, $response->getStatusCode());
		$this->assertEquals("Bad request", $response->getBody()->getContents());
	}

	public function test_run_middleware_with_thrown_exception_and_no_exception_handler_set()
	{
		$router = new Router;
		$route = $router->get("/books", function(ServerRequestInterface $request){
			throw new BadRequestHttpException("Bad request");
		});

		$application = new Application($router);

		$reflection = new \ReflectionClass($application);
		$method = $reflection->getMethod('runMiddleware');
		$method->setAccessible(true);

		$request = ServerRequest::create("get", "http://example.org/authors", null, [], [], [], []);

		$this->expectException(BadRequestHttpException::class);
		$response = $method->invokeArgs($application, [new MiddlewareManager, $request, $route]);
	}

	public function test_run_middleware_passes_path_parameters_in_order()
	{
		$router = new Router;
		$route = $router->get("/books/{isbn}/comments/{id}", function(ServerRequestInterface $request, string $isbn, string $id){
			return new Response(
				ResponseStatus::OK,
				\json_encode([
					"isbn" => $isbn,
					"id" => $id
				])
			);
		});

		$application = new Application($router);

		$reflection = new \ReflectionClass($application);
		$method = $reflection->getMethod('runMiddleware');
		$method->setAccessible(true);

		$request = ServerRequest::create("get", "http://example.org/books/123-isbn-456/comments/987-comment-654", null, [], [], [], []);

		$response = $method->invokeArgs($application, [new MiddlewareManager, $request, $route]);

		$payload = \json_decode($response->getBody()->getContents());

		$this->assertEquals("123-isbn-456", $payload->isbn);
		$this->assertEquals("987-comment-654", $payload->id);
	}

	public function test_dispatch_with_unresolvable_route()
	{
		$application = new Application(new Router);

		$this->expectException(NotFoundHttpException::class);

		$application->dispatch(
			ServerRequest::create("get", "http://example.org/authors", null, [], [], [], [])
		);
	}

	public function test_dispatch_with_resolvable_route()
	{
		$router = new Router;
		$router->get("/books", function(ServerRequestInterface $request){
			return new Response(
				ResponseStatus::OK,
				"OK"
			);
		});

		$application = new Application($router);

		$response = $application->dispatch(
			ServerRequest::create("get", "http://example.org/books", null, [], [], [], [])
		);

		$this->assertEquals(ResponseStatus::OK, $response->getStatusCode());
		$this->assertEquals("OK", $response->getBody()->getContents());
	}

	public function test_send()
	{
		$application = new Application(new Router);

		$output = "";
		\ob_start(function($buffer) use (&$output){
			$output .= $buffer;
		});

		$application->send(
			new Response(
				ResponseStatus::OK,
				"Limber send() test",
				[
					"Header1" => "Value1"
				]
			)
		);

		$responseData = \ob_get_flush();

		$this->assertEquals("Limber send() test", $responseData);
	}
}