<?php

namespace Limber\Tests;

use Capsule\Response;
use Capsule\ResponseStatus;
use Capsule\ServerRequest;
use Limber\Middleware\CallableMiddlewareLayer;
use Limber\Middleware\MiddlewareLayerInterface;
use Limber\Middleware\MiddlewareManager;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @covers Limber\Middleware\MiddlewareManager
 * @covers Limber\Middleware\CallableMiddlewareLayer
 */
class MiddlewareTest extends TestCase
{
	public function test_constructor_compiles_class_references()
	{
		$middlewareManager = new MiddlewareManager([
			SampleMiddleware::class
		]);

		$middleware = $middlewareManager->getMiddleware();

		$this->assertTrue($middleware[0] instanceof MiddlewareLayerInterface);
	}

	public function test_constructor_compiles_callables()
	{
		$middlewareManager = new MiddlewareManager([
			function(ServerRequestInterface $request, callable $next): ResponseInterface {
				$request = $request->withAddedHeader("X-Request-Header", "Limber");
				return $next($request);
			}
		]);

		$middleware = $middlewareManager->getMiddleware();

		$this->assertTrue($middleware[0] instanceof CallableMiddlewareLayer);
	}

	public function test_constructor_adds_layer_instances()
	{
		$middlewareManager = new MiddlewareManager([
			new SampleMiddleware
		]);

		$middleware = $middlewareManager->getMiddleware();

		$this->assertTrue($middleware[0] instanceof MiddlewareLayerInterface);
	}

	public function test_add()
	{
		$middlewareManager = new MiddlewareManager([
			new SampleMiddleware
		]);

		$middlewareManager->add(new SampleMiddleware);

		$middleware = $middlewareManager->getMiddleware();

		$this->assertEquals(2, \count($middleware));
		$this->assertTrue($middleware[1] instanceof MiddlewareLayerInterface);
	}

	public function test_run()
	{
		$middlewareManager = new MiddlewareManager([
			new SampleMiddleware
		]);

		$response = $middlewareManager->run(
			ServerRequest::create("get", "/books", null, [], [], [], []),
			function(ServerRequestInterface $request) {
				return new Response(
					ResponseStatus::OK
				);
			}
		);

		$this->assertEquals(ResponseStatus::OK, $response->getStatusCode());
		$this->assertEquals("X-Sample-Middleware: Limber", $response->getHeaderLine("X-Sample-Middleware"));
	}
}