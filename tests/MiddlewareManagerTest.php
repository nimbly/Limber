<?php

namespace Limber\Tests;

use Capsule\Response;
use Capsule\ResponseStatus;
use Limber\ExceptionHandlerInterface;
use Limber\Exceptions\NotFoundHttpException;
use Limber\Middleware\CallableMiddleware;
use Limber\MiddlewareManager;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * @covers Limber\MiddlewareManager
 * @covers Limber\Middleware\CallableMiddleware
 * @covers Limber\Exceptions\HttpException
 */
class MiddlewareManagerTest extends TestCase
{
	public function test_normalize_middleware(): void
	{
		$middlewareManager = new MiddlewareManager;

		$middleware = function(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
			return $handler->handle($request);
		};

		$reflection = new \ReflectionClass($middlewareManager);
		$method = $reflection->getMethod('normalizeMiddleware');
		$method->setAccessible(true);

		$this->assertEquals(
			[new CallableMiddleware($middleware)],
			$method->invoke($middlewareManager, [$middleware])
		);
	}

	public function test_handle_exception_with_exception_handler_set(): void
	{
		$middlewareManager = new MiddlewareManager;
		$middlewareManager->setExceptionHandler(
			new class implements ExceptionHandlerInterface {

				public function handle(Throwable $exception): ResponseInterface
				{
					return new Response(
						$exception->getHttpStatus(),
						$exception->getMessage()
					);
				}
			}
		);

		$reflection = new \ReflectionClass($middlewareManager);
		$method = $reflection->getMethod('handleException');
		$method->setAccessible(true);

		$response = $method->invokeArgs($middlewareManager, [new NotFoundHttpException("Route not found")]);

		$this->assertEquals(ResponseStatus::NOT_FOUND, $response->getStatusCode());
		$this->assertEquals("Route not found", $response->getBody()->getContents());
	}

	public function test_handle_exception_with_no_exception_handler_set(): void
	{
		$middlewareManager = new MiddlewareManager;

		$reflection = new \ReflectionClass($middlewareManager);
		$method = $reflection->getMethod('handleException');
		$method->setAccessible(true);

		$this->expectException(NotFoundHttpException::class);

		$method->invokeArgs($middlewareManager, [new NotFoundHttpException("Route not found")]);
	}
}