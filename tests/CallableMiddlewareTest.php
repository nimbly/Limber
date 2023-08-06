<?php

namespace Limber\Tests;

use Nimbly\Capsule\Response;
use Nimbly\Capsule\ResponseStatus;
use Nimbly\Capsule\ServerRequest;
use Nimbly\Limber\Middleware\CallableMiddleware;
use Nimbly\Limber\Middleware\RequestHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


/**
 * @covers Nimbly\Limber\Middleware\CallableMiddleware
 * @covers Nimbly\Limber\Middleware\RequestHandler
 */
class CallableMiddlewareTest extends TestCase
{
	public function test_process(): void
	{
		$callableMiddleware = new CallableMiddleware(
			function(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {

				$response = $handler->handle($request);
				$response = $response->withHeader('X-Callable-Middleware', 'OK');
				return $response;

			}
		);

		$response = $callableMiddleware->process(
			new ServerRequest("get", "http://example.org"),
			new RequestHandler(
				function(ServerRequestInterface $request): ResponseInterface {
					return new Response(
						ResponseStatus::OK,
						"Ok"
					);
				}
			)
		);

		$this->assertEquals(
			'OK',
			$response->getHeader('X-Callable-Middleware')[0]
		);
	}
}