<?php

namespace Nimbly\Limber\Tests;

use Nimbly\Capsule\Response;
use Nimbly\Capsule\ResponseStatus;
use Nimbly\Capsule\ServerRequest;
use Nimbly\Limber\Exceptions\MethodNotAllowedHttpException;
use Nimbly\Limber\Exceptions\NotFoundHttpException;
use Nimbly\Limber\Middleware\RouteResolver;
use Nimbly\Limber\MiddlewareManager;
use Nimbly\Limber\Router\Router;
use Nimbly\Limber\Tests\Fixtures\SampleMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @covers Nimbly\Limber\Middleware\RouteResolver
 */
class RouteResolveTest extends TestCase
{
	public function test_unresolvable_route(): void
	{
		$routeResolver = new RouteResolver(new Router, new MiddlewareManager);

		$this->expectException(NotFoundHttpException::class);

		$routeResolver->process(
			new ServerRequest("get", "http://example.org/authors"),
			new class implements RequestHandlerInterface {
				public function handle(ServerRequestInterface $request): ResponseInterface
				{
					return new Response(ResponseStatus::OK);
				}
			}
		);
	}

	public function test_unresolvable_route_but_other_methods_allowed(): void
	{
		$router = new Router;
		$router->get("books", "GetBooks");
		$router->post("books", "CreateBook");

		$routeResolver = new RouteResolver($router, new MiddlewareManager);

		$this->expectException(MethodNotAllowedHttpException::class);
		$routeResolver->process(
			new ServerRequest("delete", "/books"),
			new class implements RequestHandlerInterface {
				public function handle(ServerRequestInterface $request): ResponseInterface
				{
					return new Response(ResponseStatus::OK);
				}
			}
		);
	}

	public function test_methods_returned_in_method_not_allowed_exception(): void
	{
		$router = new Router;
		$router->get("books", "GetBooks");
		$router->post("books", "CreateBook");

		$routeResolver = new RouteResolver($router, new MiddlewareManager);

		try {

			$routeResolver->process(
				new ServerRequest("delete", "/books"),
				new class implements RequestHandlerInterface {
					public function handle(ServerRequestInterface $request): ResponseInterface
					{
						return new Response(ResponseStatus::OK);
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

	public function test_route_attributes_attached_to_request(): void
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

		$routeResolver = new RouteResolver($router, new MiddlewareManager);

		$response = $routeResolver->process(
			new ServerRequest("get", "http://example.org/books"),
			new class implements RequestHandlerInterface {
				public function handle(ServerRequestInterface $request): ResponseInterface
				{
					return new Response(
						ResponseStatus::OK,
						\json_encode(
							[
								"attributes" => $request->getAttributes()
							]
						)
					);
				}
			}
		);

		$payload = \json_decode($response->getBody()->getContents());

		$this->assertArrayHasKey(
			"Attribute",
			(array) $payload->attributes
		);

		$this->assertEquals(
			"Value",
			((array) $payload->attributes)["Attribute"]
		);
	}

	public function test_route_specific_middleware(): void
	{
		$router = new Router;
		$router->add(
			methods: ["get"],
			path: "/^books$/",
			middleware: [new SampleMiddleware("Routed Middleware")],
			handler: function(): Response {
				return new Response(
					ResponseStatus::NO_CONTENT,
				);
			},
		);

		$routeResolver = new RouteResolver($router, new MiddlewareManager);

		$response = $routeResolver->process(
			new ServerRequest("get", "http://example.org/books"),
			new class implements RequestHandlerInterface {
				public function handle(ServerRequestInterface $request): ResponseInterface
				{
					return new Response(
						ResponseStatus::NO_CONTENT,
					);
				}
			}
		);

		$this->assertEquals(
			"Routed Middleware",
			$response->getHeaderLine("X-Limber-Response")
		);
	}

	public function test_resolvable_route(): void
	{
		$router = new Router;
		$router->get("/books", function(ServerRequestInterface $request){
			return new Response(
				ResponseStatus::OK,
				"OK"
			);
		});

		$routeResolver = new RouteResolver($router, new MiddlewareManager);

		$response = $routeResolver->process(
			new ServerRequest("get", "/books"),
			new class implements RequestHandlerInterface {
				public function handle(ServerRequestInterface $request): ResponseInterface
				{
					return new Response(ResponseStatus::OK, "OK");
				}
			}
		);

		$this->assertEquals(ResponseStatus::OK, $response->getStatusCode());
		$this->assertEquals("OK", $response->getBody()->getContents());
	}
}