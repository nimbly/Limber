<?php

namespace Limber\Tests;

use Capsule\Response;
use Capsule\ResponseStatus;
use Capsule\ServerRequest;
use Limber\Application;
use Limber\Exceptions\ApplicationException;
use Limber\Exceptions\BadRequestHttpException;
use Limber\Exceptions\DispatchException;
use Limber\Exceptions\MethodNotAllowedHttpException;
use Limber\Exceptions\NotFoundHttpException;
use Limber\Middleware\CallableMiddleware;
use Limber\Middleware\MiddlewareManager;
use Limber\Middleware\RequestHandler;
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
 * @covers Limber\Middleware\CallableMiddleware
 * @covers Limber\Middleware\RequestHandler
 * @covers Limber\Middleware\PrepareHttpResponse
 * @covers Limber\Exceptions\HttpException
 * @covers Limber\Exceptions\MethodNotAllowedHttpException
 */
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

	public function test_normalize_middleware()
	{
		$application = new Application(
			new Router
		);

		$middleware = function(ServerRequestInterface $request, RequestHandler $handler): ResponseInterface {
			return $handler->handle($request);
		};

		$reflection = new \ReflectionClass($application);
		$method = $reflection->getMethod('normalizeMiddleware');
		$method->setAccessible(true);

		$this->assertEquals(
			[new CallableMiddleware($middleware)],
			$method->invoke($application, [$middleware])
		);
	}

	public function test_normalize_middleware_throws_exception_if_unknown_type()
	{
		$application = new Application(
			new Router
		);

		$reflection = new \ReflectionClass($application);
		$method = $reflection->getMethod('normalizeMiddleware');
		$method->setAccessible(true);

		$this->expectException(ApplicationException::class);
		$method->invoke($application, [new \stdClass]);
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

		$request = new ServerRequest("get", "http://example.org/authors");

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

		$request = new ServerRequest("get", "http://example.org/authors");

		$this->expectException(NotFoundHttpException::class);

		$response = $method->invokeArgs($application, [new NotFoundHttpException("Route not found")]);
	}

	public function test_dispatch_with_unresolvable_route()
	{
		$application = new Application(new Router);

		$this->expectException(NotFoundHttpException::class);

		$application->dispatch(
			new ServerRequest("get", "http://example.org/authors")
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
			new ServerRequest("delete", "/books")
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
				new ServerRequest("delete", "/books")
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
			new ServerRequest("get", "http://example.org/books")
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