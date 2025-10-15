<?php

namespace Nimbly\Limber\Tests;

use Nimbly\Capsule\Response;
use Nimbly\Capsule\ResponseStatus;
use Nimbly\Capsule\ServerRequest;
use Nimbly\Limber\ExceptionHandlerInterface;
use Nimbly\Limber\Exceptions\ApplicationException;
use Nimbly\Limber\Exceptions\NotFoundHttpException;
use Nimbly\Limber\MiddlewareManager;
use Nimbly\Limber\Tests\Fixtures\SampleMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;
use Throwable;

#[CoversClass(MiddlewareManager::class)]
class MiddlewareManagerTest extends TestCase
{
	public function test_compile(): void
	{
		$middleware = [
			new class implements MiddlewareInterface {
				public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
				{
					return $handler->handle(
						$request->withAddedHeader("X-Request", "Request Middleware")
					);
				}
			},

			new class implements MiddlewareInterface {
				public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
				{
					$response = $handler->handle($request);
					return $response->withAddedHeader("X-Response", "Response Middleware");
				}
			},
		];

		$middlewareManager = new MiddlewareManager;

		$compiled_middleware = $middlewareManager->compile(
			$middleware,
			new class implements RequestHandlerInterface {
				public function handle(ServerRequestInterface $request): ResponseInterface
				{
					return new Response(
						ResponseStatus::OK,
						\json_encode([
							"X_Request" => $request->getHeaderLine("X-Request")
						]),
						["Content-Type" => "application/json"]
					);
				}
			}
		);

		$request = new ServerRequest("get", "http://api.example.com");
		$response = $compiled_middleware->handle($request);

		$parsed_response = \json_decode($response->getBody());

		$this->assertEquals(
			"Request Middleware",
			$parsed_response->X_Request
		);

		$this->assertEquals(
			"Response Middleware",
			$response->getHeaderLine("X-Response")
		);
	}

	public function test_normalize_instance_middleware(): void
	{
		$middleware = new class implements MiddlewareInterface {
			public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
			{
				return $handler->handle($request);
			}
		};

		$middlewareManager = new MiddlewareManager;

		$this->assertEquals(
			[$middleware],
			$middlewareManager->normalize([$middleware])
		);
	}

	public function test_normalize_class_reference_middleware(): void
	{
		$middlewareManager = new MiddlewareManager;
		$normalized_middleware = $middlewareManager->normalize([SampleMiddleware::class]);

		$this->assertInstanceOf(
			SampleMiddleware::class,
			$normalized_middleware[0]
		);
	}

	public function test_normalize_middleware_throws_exception_if_unknown_type(): void
	{
		$middlewareManager = new MiddlewareManager;

		$this->expectException(ApplicationException::class);
		$middlewareManager->normalize([new \stdClass]);
	}

	public function test_handle_exception_with_exception_handler_set(): void
	{
		$middlewareManager = new MiddlewareManager(
			exceptionHandler: new class implements ExceptionHandlerInterface {
				public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface {
					return new Response(
						$exception->getCode(),
						$exception->getMessage()
					);
				}
			}
		);

		$reflection = new ReflectionClass($middlewareManager);
		$method = $reflection->getMethod("handleException");
		$method->setAccessible(true);

		$response = $method->invokeArgs($middlewareManager, [new NotFoundHttpException("Route not found"), new ServerRequest("get", "/")]);

		$this->assertEquals(
			ResponseStatus::NOT_FOUND->value,
			$response->getStatusCode()
		);

		$this->assertEquals(
			"Route not found",
			$response->getBody()->getContents()
		);
	}

	public function test_handle_exception_with_no_exception_handler_set(): void
	{
		$middlewareManager = new MiddlewareManager;

		$reflection = new ReflectionClass($middlewareManager);
		$method = $reflection->getMethod("handleException");
		$method->setAccessible(true);

		$this->expectException(NotFoundHttpException::class);

		$method->invokeArgs($middlewareManager, [new NotFoundHttpException("Route not found"), new ServerRequest("get", "/")]);
	}
}