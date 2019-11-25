<?php

namespace Limber\Tests;

use Capsule\Response;
use Capsule\ResponseStatus;
use Capsule\ServerRequest;
use Limber\Application;
use Limber\Exceptions\MethodNotAllowedHttpException;
use Limber\Exceptions\NotFoundHttpException;
use Limber\MiddlewareManager;
use Limber\Router\Router;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @covers Limber\Application
 * @covers Limber\Router\Router
 * @covers Limber\Router\Engines\DefaultRouter
 * @covers Limber\Router\Route
 * @covers Limber\Kernel
 * @covers Limber\Middleware\CallableMiddleware
 * @covers Limber\Middleware\RequestHandler
 * @covers Limber\Middleware\PrepareHttpResponseMiddleware
 * @covers Limber\Exceptions\HttpException
 * @covers Limber\Exceptions\MethodNotAllowedHttpException
 * @covers Limber\MiddlewareManager
 * @covers Limber\Middleware\RouteResolverMiddleware
 */
class ApplicationTest extends TestCase
{
	public function test_constructor_autocreates_middleware_manager()
	{
		$application = new Application(new Router);

		$reflection = new \ReflectionClass($application);

		$property = $reflection->getProperty('middlewareManager');
		$property->setAccessible(true);

		$this->assertInstanceOf(
			MiddlewareManager::class,
			$property->getValue($application)
		);
	}

	public function test_constructor_uses_middleware_manager()
	{
		$middlewareManager = new MiddlewareManager;

		$application = new Application(new Router, $middlewareManager);

		$reflection = new \ReflectionClass($application);
		$property = $reflection->getProperty('middlewareManager');
		$property->setAccessible(true);

		$this->assertSame(
			$middlewareManager,
			$property->getValue($application)
		);
	}

	public function test_set_middleware()
	{
		$application = new Application(
			new Router
		);

		$middleware = function(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {

			return $handler->handle($request);

		};

		$application->setMiddleware([
			$middleware
		]);

		$reflection = new \ReflectionClass($application);
		$property = $reflection->getProperty('middleware');
		$property->setAccessible(true);

		$this->assertEquals(
			[$middleware],
			$property->getValue($application)
		);
	}

	public function test_add_middleware()
	{
		$application = new Application(
			new Router
		);

		$middleware = function(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {

			return $handler->handle($request);

		};

		$application->addMiddleware($middleware);

		$reflection = new \ReflectionClass($application);
		$property = $reflection->getProperty('middleware');
		$property->setAccessible(true);

		$this->assertEquals(
			[$middleware],
			$property->getValue($application)
		);
	}

	public function test_set_exception_handler_passes_handler_to_middleware_manager()
	{
		$application = new Application(
			new Router
		);

		$handler = function(\Throwable $exception): ResponseInterface {
			return new Response(
				ResponseStatus::BAD_REQUEST,
				$exception->getMessage()
			);
		};

		$application->setExceptionHandler($handler);

		$reflection = new \ReflectionClass($application);
		$property = $reflection->getProperty('middlewareManager');
		$property->setAccessible(true);

		$middlewareManager = $property->getValue($application);

		$reflection = new \ReflectionClass($middlewareManager);
		$property = $reflection->getProperty('exceptionHandler');
		$property->setAccessible(true);

		$this->assertSame($handler, $property->getValue($middlewareManager));
	}

	public function test_exception_handling_with_exception_handler_set()
	{
		$application = new Application(new Router);
		$application->setExceptionHandler(function(\Throwable $exception): ResponseInterface {

			return new Response(
				$exception->getHttpStatus(),
				$exception->getMessage()
			);

		});

		$response = $application->dispatch(
			ServerRequest::create("get", "http://example.org/authors", null, [], [], [], [])
		);

		$this->assertEquals(ResponseStatus::NOT_FOUND, $response->getStatusCode());
		$this->assertEquals("Route not found", $response->getBody()->getContents());
	}

	public function test_handle_exception_with_no_exception_handler_set()
	{
		$application = new Application(new Router);

		$this->expectException(NotFoundHttpException::class);

		$application->dispatch(
			ServerRequest::create("get", "http://example.org/authors", null, [], [], [], [])
		);
	}

	public function test_dispatch_with_unresolvable_route()
	{
		$application = new Application(new Router);

		$this->expectException(NotFoundHttpException::class);

		$application->dispatch(
			ServerRequest::create("get", "http://example.org/authors", null, [], [], [], [])
		);
	}

	public function test_dispatch_with_unresolvable_route_but_other_methods_allowed()
	{
		$router = new Router;
		$router->get("books", "GetBooks");
		$router->post("books", "CreateBook");

		$application = new Application($router);

		$this->expectException(MethodNotAllowedHttpException::class);
		$application->dispatch(
			ServerRequest::create("delete", "/books", null, [], [], [], [])
		);
	}

	public function test_methods_returned_in_method_not_allowed_exception()
	{
		$router = new Router;
		$router->get("books", "GetBooks");
		$router->post("books", "CreateBook");

		$application = new Application($router);

		try {
			$application->dispatch(
				ServerRequest::create("delete", "/books", null, [], [], [], [])
			);
		}
		catch( MethodNotAllowedHttpException $exception ){

			$headers = $exception->getHeaders();

		}

		$this->assertEquals(
			["Allow" => "GET, HEAD, POST"],
			$headers ?? []
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