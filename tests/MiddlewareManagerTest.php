<?php

namespace Limber\Tests;

use Limber\Exceptions\ApplicationException;
use Limber\Middleware\CallableMiddleware;
use Limber\Middleware\RequestHandler;
use Limber\MiddlewareManager;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @covers Limber\MiddlewareManager
 * @covers Limber\Middleware\CallableMiddleware
 */
class MiddlewareManagerTest extends TestCase
{
	public function test_normalize_middleware()
	{
		$middlewareManager = new MiddlewareManager;

		$middleware = function(ServerRequestInterface $request, RequestHandler $handler): ResponseInterface {
			return $handler->handle($request);
		};

		$reflection = new \ReflectionClass($middlewareManager);
		$method = $reflection->getMethod('normalize');
		$method->setAccessible(true);

		$this->assertEquals(
			[new CallableMiddleware($middleware)],
			$method->invoke($middlewareManager, [$middleware])
		);
	}

	public function test_normalize_middleware_throws_exception_if_unknown_type()
	{
		$middlewareManager = new MiddlewareManager;

		$reflection = new \ReflectionClass($middlewareManager);
		$method = $reflection->getMethod('normalize');
		$method->setAccessible(true);

		$this->expectException(ApplicationException::class);
		$method->invoke($middlewareManager, [new \stdClass]);
	}
}