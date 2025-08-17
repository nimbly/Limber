<?php

namespace Nimbly\Limber\Tests;

use DateTime;
use Nimbly\Capsule\Response;
use Nimbly\Capsule\ResponseStatus;
use Nimbly\Capsule\ServerRequest;
use Nimbly\Carton\Container;
use Nimbly\Limber\Exceptions\RouteException;
use Nimbly\Limber\Kernel;
use Nimbly\Limber\Router\Route;
use PHPUnit\Framework\TestCase;

/**
 * @covers Nimbly\Limber\Kernel
 */
class KernelTest extends TestCase
{
	public function test_route_attribute_not_found_throws_route_exception(): void
	{
		$kernel = new Kernel;
		$this->expectException(RouteException::class);
		$kernel->handle(new ServerRequest("get", "http://api.example.com/"));
	}

	public function test_route_handler_called(): void
	{
		$route = new Route(["get"], "/foo", function(): Response {return new Response(ResponseStatus::ACCEPTED, "Okay");});
		$request = new ServerRequest("get", "http://api.example.com/");
		$request = $request->withAttribute(Route::class, $route);

		$kernel = new Kernel;
		$response = $kernel->handle($request);

		$this->assertEquals(
			ResponseStatus::ACCEPTED->value,
			$response->getStatusCode()
		);
	}

	public function test_route_handler_called_with_container(): void
	{
		$container = new Container;
		$container->set(DateTime::class, new DateTime("2020-01-01"));

		$route = new Route(
			["get"],
			"/foo",
			function(DateTime $date): Response {
				return new Response(ResponseStatus::ACCEPTED, $date->format("c"));
			}
		);

		$request = new ServerRequest("get", "http://api.example.com/");
		$request = $request->withAttribute(Route::class, $route);

		$kernel = new Kernel($container);
		$response = $kernel->handle($request);

		$this->assertEquals(
			ResponseStatus::ACCEPTED->value,
			$response->getStatusCode()
		);

		$this->assertEquals(
			"2020-01-01T00:00:00+00:00",
			$response->getBody()->getContents()
		);
	}

	public function test_route_handler_called_with_path_parameters(): void
	{
		$route = new Route(
			["get"],
			"/foo/{id:uuid}",
			function(string $id): Response {
				return new Response(ResponseStatus::ACCEPTED, $id);
			}
		);

		$request = new ServerRequest("get", "http://api.example.com/foo/df043d48-fc96-4b4a-90c3-efe71df61ff7");
		$request = $request->withAttribute(Route::class, $route);

		$kernel = new Kernel;
		$response = $kernel->handle($request);

		$this->assertEquals(
			ResponseStatus::ACCEPTED->value,
			$response->getStatusCode()
		);

		$this->assertEquals(
			"df043d48-fc96-4b4a-90c3-efe71df61ff7",
			$response->getBody()->getContents()
		);
	}

	public function test_route_handler_called_with_request_attributes(): void
	{
		$route = new Route(
			["get"],
			"/foo",
			function(Route $route): Response {
				return new Response(ResponseStatus::ACCEPTED, $route->getPath());
			}
		);

		$request = new ServerRequest("get", "http://api.example.com/foo/df043d48-fc96-4b4a-90c3-efe71df61ff7");
		$request = $request->withAttribute(Route::class, $route);

		$kernel = new Kernel;
		$response = $kernel->handle($request);

		$this->assertEquals(
			ResponseStatus::ACCEPTED->value,
			$response->getStatusCode()
		);

		$this->assertEquals(
			"/foo",
			$response->getBody()->getContents()
		);
	}
}