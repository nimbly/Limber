<?php

namespace Limber\Tests;

use Capsule\Response;
use Capsule\ResponseStatus;
use Capsule\ServerRequest;
use Carton\Container;
use DateTime;
use Limber\Application;
use Limber\EmptyStream;
use Limber\Exceptions\ApplicationException;
use Limber\Exceptions\DependencyResolutionException;
use Limber\Exceptions\HttpException;
use Limber\Exceptions\MethodNotAllowedHttpException;
use Limber\Exceptions\NotFoundHttpException;
use Limber\Middleware\CallableMiddleware;
use Limber\Middleware\RequestHandler;
use Limber\Router\Route;
use Limber\Router\Router;
use Limber\Router\RouterInterface;
use Limber\Tests\Fixtures\ConstructorClass;
use Limber\Tests\Fixtures\InvokableClass;
use Limber\Tests\Fixtures\SampleMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;
use ReflectionFunction;

/**
 * @covers Limber\Application
 * @covers Limber\Router\Router
 * @covers Limber\Router\Engines\DefaultRouter
 * @covers Limber\Router\Route
 * @covers Limber\Middleware\CallableMiddleware
 * @covers Limber\Middleware\RequestHandler
 * @covers Limber\Middleware\PrepareHttpResponse
 * @covers Limber\Exceptions\ApplicationException
 * @covers Limber\Exceptions\RouteException
 * @covers Limber\Exceptions\HttpException
 * @covers Limber\Exceptions\MethodNotAllowedHttpException
 * @covers Limber\Exceptions\NotFoundHttpException
 * @covers Limber\Exceptions\DependencyResolutionException
 * @covers Limber\EmptyStream
 *
 * @uses Limber\Router\RouterInterface
 */
class ApplicationTest extends TestCase
{
	public function test_constructor(): void
	{
		$router = new Router;

		$application = new Application($router);

		$reflection = new ReflectionClass($application);

		$property = $reflection->getProperty('router');
		$property->setAccessible(true);

		$this->assertSame(
			$router,
			$property->getValue($application)
		);
	}

	public function test_set_container(): void
	{
		$application = new Application(
			new Router
		);

		$container = new Container;
		$application->setContainer($container);

		$reflection = new ReflectionClass($application);

		$property = $reflection->getProperty('container');
		$property->setAccessible(true);

		$this->assertSame(
			$container,
			$property->getValue($application)
		);
	}

	public function test_set_middleware(): void
	{
		$application = new Application(
			new Router
		);

		$middleware = function(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {

			return $handler->handle($request);

		};

		$application->setMiddleware([$middleware]);

		$reflection = new ReflectionClass($application);

		$property = $reflection->getProperty('middleware');
		$property->setAccessible(true);

		$this->assertEquals(
			[$middleware],
			$property->getValue($application)
		);
	}

	public function test_add_middleware(): void
	{
		$application = new Application(
			new Router
		);

		$middleware = function(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {

			return $handler->handle($request);

		};

		$application->addMiddleware($middleware);

		$reflection = new ReflectionClass($application);
		$property = $reflection->getProperty('middleware');
		$property->setAccessible(true);

		$this->assertEquals(
			[$middleware],
			$property->getValue($application)
		);
	}

	public function test_normalize_callable_middleware(): void
	{
		$application = new Application(
			new Router
		);

		$middleware = function(ServerRequestInterface $request, RequestHandler $handler): ResponseInterface {
			return $handler->handle($request);
		};

		$reflection = new ReflectionClass($application);
		$method = $reflection->getMethod('normalizeMiddleware');
		$method->setAccessible(true);

		$this->assertEquals(
			[new CallableMiddleware($middleware)],
			$method->invoke($application, [$middleware])
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
		$method = $reflection->getMethod('normalizeMiddleware');
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
		$method = $reflection->getMethod('normalizeMiddleware');
		$method->setAccessible(true);

		$normalized_middleware = $method->invoke($application, [SampleMiddleware::class]);

		$this->assertInstanceOf(
			SampleMiddleware::class,
			$normalized_middleware[0]
		);
	}

	public function test_normalize_class_reference_middleware_with_user_args(): void
	{
		$application = new Application(
			new Router
		);

		$reflection = new ReflectionClass($application);
		$method = $reflection->getMethod('normalizeMiddleware');
		$method->setAccessible(true);

		$normalized_middleware = $method->invoke($application, [
			SampleMiddleware::class => ["param" => "bar"]
		]);

		$this->assertInstanceOf(
			SampleMiddleware::class,
			$normalized_middleware[0]
		);

		$this->assertEquals(
			"bar",
			$normalized_middleware[0]->getParam()
		);
	}

	public function test_normalize_middleware_throws_exception_if_unknown_type(): void
	{
		$application = new Application(
			new Router
		);

		$reflection = new ReflectionClass($application);
		$method = $reflection->getMethod('normalizeMiddleware');
		$method->setAccessible(true);

		$this->expectException(ApplicationException::class);
		$method->invoke($application, [new \stdClass]);
	}

	public function test_set_exception_handler(): void
	{
		$application = new Application(
			new Router
		);

		$handler = function(\Throwable $exception): void {};

		$application->setExceptionHandler($handler);

		$reflection = new ReflectionClass($application);
		$property = $reflection->getProperty('exceptionHandler');
		$property->setAccessible(true);

		$this->assertSame($handler, $property->getValue($application));
	}

	public function test_handle_exception_with_exception_handler_set(): void
	{
		$application = new Application(new Router);
		$application->setExceptionHandler(function(HttpException $exception): ResponseInterface {

			return new Response(
				$exception->getHttpStatus(),
				$exception->getMessage()
			);

		});

		$reflection = new ReflectionClass($application);
		$method = $reflection->getMethod('handleException');
		$method->setAccessible(true);

		$response = $method->invokeArgs($application, [new NotFoundHttpException("Route not found"), new ServerRequest("get", "/")]);

		$this->assertEquals(ResponseStatus::NOT_FOUND, $response->getStatusCode());
		$this->assertEquals("Route not found", $response->getBody()->getContents());
	}

	public function test_handle_exception_with_no_exception_handler_set(): void
	{
		$application = new Application(new Router);

		$reflection = new ReflectionClass($application);
		$method = $reflection->getMethod('handleException');
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
		$route = (new Route(["get"], "/books", function(ServerRequestInterface $request){
			return new Response(
				ResponseStatus::OK,
				\json_encode(
					[
						"attributes" => $request->getAttributes()
					]
				)
			);
		}))->setAttribute("Attribute", "Value");

		$application = new Application(
			new Router([$route])
		);

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
		$reflectionMethod = $reflectionClass->getMethod('attachRequestAttributes');
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

	public function test_get_parameters_for_callable_on_closure(): void
	{
		$application = new Application(
			new Router
		);

		$reflectionClass = new ReflectionClass($application);
		$reflectionMethod = $reflectionClass->getMethod('getParametersForCallable');
		$reflectionMethod->setAccessible(true);

		$callable = function(string $firstname, string $lastname): void {
			echo "{$firstname} {$lastname}";
		};

		$parameters = $reflectionMethod->invokeArgs($application, [$callable]);

		$this->assertCount(
			2,
			$parameters
		);
	}

	public function test_get_parameters_for_callable_on_array(): void
	{
		$application = new Application(
			new Router
		);

		$reflectionClass = new ReflectionClass($application);
		$reflectionMethod = $reflectionClass->getMethod('getParametersForCallable');
		$reflectionMethod->setAccessible(true);

		$callable = [new DateTime, "format"];

		$parameters = $reflectionMethod->invokeArgs($application, [$callable]);

		$this->assertCount(
			1,
			$parameters
		);
	}

	public function test_get_parameters_for_callable_on_invokable_instance(): void
	{
		$application = new Application(
			new Router
		);

		$reflectionClass = new ReflectionClass($application);
		$reflectionMethod = $reflectionClass->getMethod('getParametersForCallable');
		$reflectionMethod->setAccessible(true);

		$callable = new InvokableClass;

		$parameters = $reflectionMethod->invokeArgs($application, [$callable]);

		$this->assertCount(
			2,
			$parameters
		);
	}

	public function test_get_parameters_for_callable_on_string(): void
	{
		$application = new Application(
			new Router
		);

		$reflectionClass = new ReflectionClass($application);
		$reflectionMethod = $reflectionClass->getMethod('getParametersForCallable');
		$reflectionMethod->setAccessible(true);

		$callable = "\strtolower";

		$parameters = $reflectionMethod->invokeArgs($application, [$callable]);

		$this->assertCount(
			1,
			$parameters
		);
	}

	public function test_resolve_dependencies_with_primitive_in_user_args(): void
	{
		$application = new Application(
			new Router
		);

		$reflectionClass = new ReflectionClass($application);
		$reflectionMethod = $reflectionClass->getMethod('resolveDependencies');
		$reflectionMethod->setAccessible(true);

		$callable = function(string $firstname, string $lastname): void {
			echo "{$firstname} {$lastname}";
		};

		$reflectionFunction = new ReflectionFunction($callable);

		$dependencies = $reflectionMethod->invokeArgs($application, [$reflectionFunction->getParameters(), ["firstname" => "Nimbly", "lastname" => "Limber"]]);

		$this->assertEquals(
			["Nimbly", "Limber"],
			$dependencies
		);
	}

	public function test_resolve_dependencies_with_primitive_using_default_value(): void
	{
		$application = new Application(
			new Router
		);

		$reflectionClass = new ReflectionClass($application);
		$reflectionMethod = $reflectionClass->getMethod('resolveDependencies');
		$reflectionMethod->setAccessible(true);

		$callable = function(string $firstname, string $lastname = "Limber"): void {
			echo "{$firstname} {$lastname}";
		};

		$reflectionFunction = new ReflectionFunction($callable);

		$dependencies = $reflectionMethod->invokeArgs($application, [$reflectionFunction->getParameters(), ["firstname" => "Nimbly"]]);

		$this->assertEquals(
			["Nimbly", "Limber"],
			$dependencies
		);
	}

	public function test_resolve_dependencies_with_primitive_using_optional_or_allows_null(): void
	{
		$application = new Application(
			new Router
		);

		$reflectionClass = new ReflectionClass($application);
		$reflectionMethod = $reflectionClass->getMethod('resolveDependencies');
		$reflectionMethod->setAccessible(true);

		$callable = function(string $firstname, ?string $lastname): void {
			echo "{$firstname} {$lastname}";
		};

		$reflectionFunction = new ReflectionFunction($callable);

		$dependencies = $reflectionMethod->invokeArgs($application, [$reflectionFunction->getParameters(), ["firstname" => "Nimbly"]]);

		$this->assertEquals(
			["Nimbly", null],
			$dependencies
		);
	}

	public function test_resolve_dependencies_with_class_using_container(): void
	{
		$application = new Application(
			new Router
		);

		$container = new Container;
		$container->set(Application::class, $application);

		$application->setContainer($container);

		$reflectionClass = new ReflectionClass($application);
		$reflectionMethod = $reflectionClass->getMethod('resolveDependencies');
		$reflectionMethod->setAccessible(true);

		$callable = function(Application $application): bool {
			return true;
		};

		$reflectionFunction = new ReflectionFunction($callable);

		$dependencies = $reflectionMethod->invokeArgs($application, [$reflectionFunction->getParameters()]);

		$this->assertEquals(
			[$application],
			$dependencies
		);
	}

	public function test_resolve_dependencies_with_server_request_instance(): void
	{
		$application = new Application(
			new Router
		);

		$reflectionClass = new ReflectionClass($application);
		$reflectionMethod = $reflectionClass->getMethod('resolveDependencies');
		$reflectionMethod->setAccessible(true);

		$callable = function(ServerRequestInterface $request): ResponseInterface {
			return new Response(
				ResponseStatus::OK
			);
		};

		$reflectionFunction = new ReflectionFunction($callable);

		$serverRequest = new ServerRequest("GET", "/");

		$dependencies = $reflectionMethod->invokeArgs($application, [$reflectionFunction->getParameters(), [ServerRequestInterface::class => $serverRequest]]);

		$this->assertEquals(
			[$serverRequest],
			$dependencies
		);
	}

	public function test_resolve_dependencies_with_making_class_with_constructor(): void
	{
		$application = new Application(
			new Router
		);

		$reflectionClass = new ReflectionClass($application);
		$reflectionMethod = $reflectionClass->getMethod('resolveDependencies');
		$reflectionMethod->setAccessible(true);

		$callable = function(ConstructorClass $class): void {
			echo $class->getParam1();
		};

		$reflectionFunction = new ReflectionFunction($callable);

		$dependencies = $reflectionMethod->invokeArgs(
			$application,
			[
				$reflectionFunction->getParameters(),
				[
					"param1" => ":param1:"
				]
			]
		);

		$this->assertInstanceOf(
			ConstructorClass::class,
			$dependencies[0]
		);
	}

	public function test_resolve_dependencies_with_unresolvable_throws_application_exception(): void
	{
		$application = new Application(
			new Router
		);

		$reflectionClass = new ReflectionClass($application);
		$reflectionMethod = $reflectionClass->getMethod('resolveDependencies');
		$reflectionMethod->setAccessible(true);

		$callable = function(string $dateTime): void {
			echo "The date is now: " . $dateTime;
		};

		$reflectionFunction = new ReflectionFunction($callable);

		$this->expectException(DependencyResolutionException::class);
		$reflectionMethod->invokeArgs($application, [$reflectionFunction->getParameters()]);
	}

	public function test_call(): void
	{
		$application = new Application(
			new Router
		);

		$name = $application->call(
			function(string $name): string {
				return $name;
			},
			[
				"name" => "Limber"
			]
		);

		$this->assertEquals(
			"Limber",
			$name
		);
	}

	public function test_make_with_class_already_in_container(): void
	{
		$application = new Application(
			new Router
		);

		$container = new Container;

		$instance = new ConstructorClass(":param1:");

		$container->set(ConstructorClass::class, $instance);

		$application->setContainer($container);

		$this->assertSame(
			$instance,
			$application->make(ConstructorClass::class)
		);
	}

	public function test_make_with_interface_throws_application_exception(): void
	{
		$application = new Application(
			new Router
		);

		$this->expectException(DependencyResolutionException::class);
		$application->make(RouterInterface::class);
	}

	public function test_make_with_abstract_throws_application_exception(): void
	{
		$application = new Application(
			new Router
		);

		$this->expectException(DependencyResolutionException::class);
		$application->make(HttpException::class);
	}

	public function test_make_on_class_with_no_constructor(): void
	{
		$application = new Application(
			new Router
		);

		$instance = $application->make(EmptyStream::class);

		$this->assertInstanceOf(
			EmptyStream::class,
			$instance
		);
	}

	public function test_make_on_class_with_constructor(): void
	{
		$application = new Application(
			new Router
		);

		$instance = $application->make(
			ConstructorClass::class,
			[
				"param1" => ":param1:"
			]
		);

		$this->assertInstanceOf(
			ConstructorClass::class,
			$instance
		);
	}

	public function test_make_on_class_with_constructor_and_user_args(): void
	{
		$application = new Application(
			new Router
		);

		$instance = $application->make(
			ConstructorClass::class,
			[
				"param1" => ":param1:"
			]
		);

		$this->assertInstanceOf(
			ConstructorClass::class,
			$instance
		);

		$this->assertEquals(
			":param1:",
			$instance->getParam1()
		);
	}
}