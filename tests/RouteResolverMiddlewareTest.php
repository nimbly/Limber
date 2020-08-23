<?php

namespace Limber\Tests;

use Capsule\Response;
use Capsule\ResponseStatus;
use Capsule\ServerRequest;
use Limber\Exceptions\MethodNotAllowedHttpException;
use Limber\Exceptions\NotFoundHttpException;
use Limber\Middleware\RouteResolver;
use Limber\MiddlewareManager;
use Limber\RouteManager;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @covers Limber\Middleware\RouteResolver
 * @covers Limber\RouteManager
 * @covers Limber\Router\Engines\DefaultRouter
 * @covers Limber\Router\Route
 * @covers Limber\MiddlewareManager
 * @covers Limber\Exceptions\HttpException
 * @covers Limber\Exceptions\MethodNotAllowedHttpException
 */
class RouteResolverMiddlewareTest extends TestCase
{
	public function test_dispatch_with_unresolvable_route(): void
	{
		$routeResolver = new RouteResolver(
			new RouteManager,
			new MiddlewareManager
		);

		$this->expectException(NotFoundHttpException::class);

		$routeResolver->process(
			new ServerRequest("get", "http://example.org/authors"),
			new class implements RequestHandlerInterface {
				public function handle(ServerRequestInterface $request): ResponseInterface
				{
					return new Response(
						ResponseStatus::OK
					);
				}
			}
		);
	}

	public function test_dispatch_with_resolvable_route(): void
	{
		$routeManager = new RouteManager;
		$routeManager->get("/authors", "Callable@Handler");

		$routeResolver = new RouteResolver(
			$routeManager,
			new MiddlewareManager
		);

		$response = $routeResolver->process(
			new ServerRequest("get", "http://example.org/authors"),
			new class implements RequestHandlerInterface {
				public function handle(ServerRequestInterface $request): ResponseInterface
				{
					return new Response(
						ResponseStatus::OK,
						"OK"
					);
				}
			}
		);

		$this->assertEquals(ResponseStatus::OK, $response->getStatusCode());
		$this->assertEquals("OK", $response->getBody()->getContents());
	}

	public function test_dispatch_with_unresolvable_route_but_other_methods_allowed(): void
	{
		$routeManager = new RouteManager;
		$routeManager->get("books", "GetBooks");
		$routeManager->post("books", "CreateBook");

		$routeResolver = new RouteResolver(
			$routeManager,
			new MiddlewareManager
		);

		$this->expectException(MethodNotAllowedHttpException::class);

		$routeResolver->process(
			new ServerRequest("delete", "/books"),
			new class implements RequestHandlerInterface {
				public function handle(ServerRequestInterface $request): ResponseInterface
				{
					return new Response(
						ResponseStatus::OK,
						"OK"
					);
				}
			}
		);
	}

	public function test_methods_returned_in_method_not_allowed_exception(): void
	{
		$routeManager = new RouteManager;
		$routeManager->get("books", "GetBooks");
		$routeManager->post("books", "CreateBook");

		$routeResolver = new RouteResolver(
			$routeManager,
			new MiddlewareManager
		);

		try {
			$routeResolver->process(
				new ServerRequest("delete", "/books"),
				new class implements RequestHandlerInterface {
					public function handle(ServerRequestInterface $request): ResponseInterface
					{
						return new Response(
							ResponseStatus::OK,
							"OK"
						);
					}
				}
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
}