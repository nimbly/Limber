<?php

namespace Limber\Tests;

use Carton\Container;
use Nimbly\Capsule\Response;
use Nimbly\Capsule\ResponseStatus;
use Nimbly\Capsule\ServerRequest;
use Nimbly\Limber\Application;
use Nimbly\Limber\ExceptionHandlerInterface;
use Nimbly\Limber\Exceptions\ApplicationException;
use Nimbly\Limber\Exceptions\MethodNotAllowedHttpException;
use Nimbly\Limber\Exceptions\NotFoundHttpException;
use Nimbly\Limber\Router\Router;
use Nimbly\Limber\Tests\Fixtures\SampleMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;
use Throwable;

/**
 * @covers Nimbly\Limber\Application
 * @covers Nimbly\Limber\Router\Router
 * @covers Nimbly\Limber\Router\Route
 * @covers Nimbly\Limber\Middleware\RequestHandler
 * @covers Nimbly\Limber\Middleware\PrepareHttpResponse
 * @covers Nimbly\Limber\Exceptions\ApplicationException
 * @covers Nimbly\Limber\Exceptions\RouteException
 * @covers Nimbly\Limber\Exceptions\HttpException
 * @covers Nimbly\Limber\Exceptions\MethodNotAllowedHttpException
 * @covers Nimbly\Limber\Exceptions\NotFoundHttpException
 * @covers Nimbly\Limber\EmptyStream
 *
 * @uses Nimbly\Limber\Router\RouterInterface
 */
class ApplicationTest extends TestCase
{
	public function test_constructor(): void
	{
		$router = new Router;
		$middleware = [FooMiddleware::class];
		$container = new Container;
		$exceptionHandler = new class implements ExceptionHandlerInterface {
			public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
			{
				return new Response(204);
			}
		};


		$application = new Application(
			$router,
			$middleware,
			$container,
			$exceptionHandler);

		$reflection = new ReflectionClass($application);

		$property = $reflection->getProperty("router");
		$property->setAccessible(true);

		$this->assertSame(
			$router,
			$property->getValue($application)
		);

		$property = $reflection->getProperty("middleware");
		$property->setAccessible(true);

		$this->assertEquals(
			$middleware,
			$property->getValue($application)
		);

		$property = $reflection->getProperty("container");
		$property->setAccessible(true);

		$this->assertSame(
			$container,
			$property->getValue($application)
		);

		$property = $reflection->getProperty("exceptionHandler");
		$property->setAccessible(true);

		$this->assertSame(
			$exceptionHandler,
			$property->getValue($application)
		);
	}

	public function test_normalize_instance_middleware(): void
	{
		$application = new Application(
			new Router
		);

		$middleware = new class implements MiddlewareInterface {

			public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
			{
				return $handler->handle($request);
			}

		};

		$reflection = new ReflectionClass($application);
		$method = $reflection->getMethod("normalizeMiddleware");
		$method->setAccessible(true);

		$this->assertEquals(
			[$middleware],
			$method->invoke($application, [$middleware])
		);
	}

	public function test_normalize_class_reference_middleware(): void
	{
		$application = new Application(
			new Router
		);

		$reflection = new ReflectionClass($application);
		$method = $reflection->getMethod("normalizeMiddleware");
		$method->setAccessible(true);

		$normalized_middleware = $method->invoke($application, [SampleMiddleware::class]);

		$this->assertInstanceOf(
			SampleMiddleware::class,
			$normalized_middleware[0]
		);
	}

	public function test_normalize_middleware_throws_exception_if_unknown_type(): void
	{
		$application = new Application(
			new Router
		);

		$reflection = new ReflectionClass($application);
		$method = $reflection->getMethod("normalizeMiddleware");
		$method->setAccessible(true);

		$this->expectException(ApplicationException::class);
		$method->invoke($application, [new \stdClass]);
	}

	public function test_handle_exception_with_exception_handler_set(): void
	{
		$application = new Application(
			router: new Router,
			exceptionHandler: new class implements ExceptionHandlerInterface {
				public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface {
					return new Response(
						$exception->getCode(),
						$exception->getMessage()
					);
				}
			}
		);

		$reflection = new ReflectionClass($application);
		$method = $reflection->getMethod("handleException");
		$method->setAccessible(true);

		$response = $method->invokeArgs($application, [new NotFoundHttpException("Route not found"), new ServerRequest("get", "/")]);

		$this->assertEquals(ResponseStatus::NOT_FOUND, $response->getStatusCode());
		$this->assertEquals("Route not found", $response->getBody()->getContents());
	}

	public function test_handle_exception_with_no_exception_handler_set(): void
	{
		$application = new Application(new Router);

		$reflection = new ReflectionClass($application);
		$method = $reflection->getMethod("handleException");
		$method->setAccessible(true);

		$this->expectException(NotFoundHttpException::class);

		$method->invokeArgs($application, [new NotFoundHttpException("Route not found"), new ServerRequest("get", "/")]);
	}

	public function test_dispatch_with_unresolvable_route(): void
	{
		$application = new Application(new Router);

		$this->expectException(NotFoundHttpException::class);

		$application->dispatch(
			new ServerRequest("get", "http://example.org/authors")
		);
	}

	public function test_dispatch_with_unresolvable_route_but_other_methods_allowed(): void
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

	public function test_methods_returned_in_method_not_allowed_exception(): void
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

	public function test_dispatch_with_resolvable_route(): void
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

	public function test_dispatch_attaches_route_attributes_to_request(): void
	{
		$router = new Router;
		$router->add(
			methods: ["get"],
			path: "/^books$/",
			handler: function(ServerRequestInterface $request) {
				return new Response(
					ResponseStatus::OK,
					\json_encode(
						[
							"attributes" => $request->getAttributes()
						]
					)
				);
			},
			attributes: [
				"Attribute" => "Value"
			]
		);

		$application = new Application($router);

		$response = $application->dispatch(
			new ServerRequest("get", "http://example.org/books")
		);

		$payload = \json_decode($response->getBody()->getContents());

		$this->assertEquals(
			[
				"Attribute" => "Value"
			],
			(array) $payload->attributes
		);
	}

	public function test_attach_request_attributes(): void
	{
		$application = new Application(
			new Router
		);

		$reflectionClass = new ReflectionClass($application);
		$reflectionMethod = $reflectionClass->getMethod("attachRequestAttributes");
		$reflectionMethod->setAccessible(true);

		$request = $reflectionMethod->invokeArgs(
			$application,
			[new ServerRequest("post", "/test"), ["Attribute" => "Value"]]
		);

		$this->assertEquals(
			$request->getAttribute("Attribute"),
			"Value"
		);
	}

	public function test_send(): void
	{
		$application = new Application(new Router);

		$output = "";
		\ob_start(function(string $buffer) use (&$output){
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