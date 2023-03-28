<?php

namespace Nimbly\Limber\Tests;

use Nimbly\Capsule\Response;
use Nimbly\Capsule\ResponseStatus;
use Nimbly\Capsule\ServerRequest;
use Nimbly\Limber\Application;
use Nimbly\Limber\ExceptionHandlerInterface;
use Nimbly\Limber\Exceptions\ApplicationException;
use Nimbly\Limber\Exceptions\NotFoundHttpException;
use Nimbly\Limber\MiddlewareManager;
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
 * @covers Nimbly\Limber\MiddlewareManager
 */
class MiddlewareManagerTest extends TestCase
{
	public function test_normalize_instance_middleware(): void
	{
		$middlewareManager = new MiddlewareManager;

		$middleware = new class implements MiddlewareInterface {
			public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
			{
				return $handler->handle($request);
			}
		};

		$reflection = new ReflectionClass($middlewareManager);
		$method = $reflection->getMethod("normalize");
		$method->setAccessible(true);

		$this->assertEquals(
			[$middleware],
			$method->invoke($middlewareManager, [$middleware])
		);
	}

	public function test_normalize_class_reference_middleware(): void
	{
		$middlewareManager = new MiddlewareManager;

		$reflection = new ReflectionClass($middlewareManager);
		$method = $reflection->getMethod("normalize");
		$method->setAccessible(true);

		$normalized_middleware = $method->invoke($middlewareManager, [SampleMiddleware::class]);

		$this->assertInstanceOf(
			SampleMiddleware::class,
			$normalized_middleware[0]
		);
	}

	public function test_normalize_middleware_throws_exception_if_unknown_type(): void
	{
		$middlewareManager = new MiddlewareManager;

		$reflection = new ReflectionClass($middlewareManager);
		$method = $reflection->getMethod("normalize");
		$method->setAccessible(true);

		$this->expectException(ApplicationException::class);
		$method->invoke($middlewareManager, [new \stdClass]);
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

		$this->assertEquals(ResponseStatus::NOT_FOUND, $response->getStatusCode());
		$this->assertEquals("Route not found", $response->getBody()->getContents());
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