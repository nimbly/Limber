<?php

namespace Nimbly\Limber\Tests;

use Nimbly\Capsule\ServerRequest;
use Nimbly\Limber\Router\Route;
use Nimbly\Limber\Router\Router;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @covers Nimbly\Limber\Router\Router
 * @covers Nimbly\Limber\Router\Route
 *
 * @uses Nimbly\Limber\Router\RouterInterface
 */
class RouterTest extends TestCase
{
	public function test_set_get_pattern(): void
	{
		Router::setPattern("isbn", "\d{3}\-\d\-\d{3}\-\d{5}\-\d");

		$this->assertEquals(
			Router::getPattern("isbn"),
			"\d{3}\-\d\-\d{3}\-\d{5}\-\d"
		);
	}

	public function test_get_pattern_not_found(): void
	{
		$this->assertNull(Router::getPattern("ean13"));
	}

	public function test_constructor_can_build_routes(): void
	{
		$route1 = new Route(["get", "post"], "/books", "BooksHandler@create");
		$route2 = new Route(["patch"], "/books", "BooksHandler@update");
		$routes = [$route1, $route2];

		$router = new Router($routes);

		$reflectionClass = new ReflectionClass($router);
		$reflectionProperty = $reflectionClass->getProperty("routes");
		$reflectionProperty->setAccessible(true);
		$assigned_routes = $reflectionProperty->getValue($router);

		$this->assertCount(1, $assigned_routes["GET"]);
		$this->assertCount(1, $assigned_routes["POST"]);
		$this->assertCount(1, $assigned_routes["PATCH"]);

		$this->assertEquals(
			$assigned_routes["GET"][0],
			$route1
		);

		$this->assertEquals(
			$assigned_routes["POST"][0],
			$route1
		);

		$this->assertEquals(
			$assigned_routes["PATCH"][0],
			$route2
		);
	}

	public function test_add(): void
	{
		$router = new Router;
		$route = $router->get("books/{id}", "BooksHandler@get");

		$this->assertEquals(
			["GET", "HEAD"],
			$route->getMethods()
		);

		$this->assertEquals(
			"books/{id}",
			$route->getPath()
		);

		$this->assertEquals(
			"BooksHandler@get",
			$route->getHandler()
		);
	}

	public function test_resolve_returns_route(): void
	{
		$router = new Router;
		$route = $router->get("books/{id}", "BooksHandler@get");

		$resolved_route = $router->resolve(
			new ServerRequest("get", "/books/12345")
		);

		$this->assertEquals(
			$route,
			$resolved_route
		);
	}

	public function test_resolve_unmatch_returns_null(): void
	{
		$router = new Router;
		$route = $router->get("books/{id}", "BooksHandler@get");

		$resolved_route = $router->resolve(
			new ServerRequest("get", "/books")
		);

		$this->assertNull($resolved_route);
	}

	public function test_get_supported_methods(): void
	{
		$router = new Router;
		$route = $router->add(["get", "put"], "books/{id}", "BooksHandler@get");

		$supported_methods = $router->getSupportedMethods(
			new ServerRequest("patch", "/books/1234")
		);

		$this->assertEquals(
			["GET", "PUT"],
			$supported_methods
		);
	}

	public function test_get_supported_methods_empty_if_no_match(): void
	{
		$router = new Router;
		$route = $router->add(["get", "put"], "books/{id}", "BooksHandler@get");

		$supported_methods = $router->getSupportedMethods(
			new ServerRequest("patch", "/books")
		);

		$this->assertEmpty($supported_methods);
	}

	public function test_adding_get_route(): void
	{
		$router = new Router;
		$route = $router->get("books/{id}", "BooksHandler@get");

		$this->assertEquals(
			["GET", "HEAD"],
			$route->getMethods()
		);

		$this->assertEquals(
			"books/{id}",
			$route->getPath()
		);

		$this->assertEquals(
			"BooksHandler@get",
			$route->getHandler()
		);
	}

	public function test_adding_post_route(): void
	{
		$router = new Router;
		$route = $router->post("books", "BooksHandler@post");

		$this->assertEquals(
			["POST"],
			$route->getMethods()
		);

		$this->assertEquals(
			"books",
			$route->getPath()
		);

		$this->assertEquals(
			"BooksHandler@post",
			$route->getHandler()
		);
	}

	public function test_adding_put_route(): void
	{
		$router = new Router;
		$route = $router->put("books", "BooksHandler@put");

		$this->assertEquals(
			["PUT"],
			$route->getMethods()
		);

		$this->assertEquals(
			"books",
			$route->getPath()
		);

		$this->assertEquals(
			"BooksHandler@put",
			$route->getHandler()
		);
	}

	public function test_adding_patch_route(): void
	{
		$router = new Router;
		$route = $router->patch("books/{id}", "BooksHandler@patch");

		$this->assertEquals(
			["PATCH"],
			$route->getMethods()
		);

		$this->assertEquals(
			"books/{id}",
			$route->getPath()
		);

		$this->assertEquals(
			"BooksHandler@patch",
			$route->getHandler()
		);
	}

	public function test_adding_delete_route(): void
	{
		$router = new Router;
		$route = $router->delete("books/{id}", "BooksHandler@delete");

		$this->assertEquals(
			["DELETE"],
			$route->getMethods()
		);

		$this->assertEquals(
			"books/{id}",
			$route->getPath()
		);

		$this->assertEquals(
			"BooksHandler@delete",
			$route->getHandler()
		);
	}

	public function test_adding_head_route(): void
	{
		$router = new Router;
		$route = $router->head("books/{id}", "BooksHandler@head");

		$this->assertEquals(
			["HEAD"],
			$route->getMethods()
		);

		$this->assertEquals(
			"books/{id}",
			$route->getPath()
		);

		$this->assertEquals(
			"BooksHandler@head",
			$route->getHandler()
		);
	}

	public function test_adding_options_route(): void
	{
		$router = new Router;
		$route = $router->options("books/{id}", "BooksHandler@options");

		$this->assertEquals(
			["OPTIONS"],
			$route->getMethods()
		);

		$this->assertEquals(
			"books/{id}",
			$route->getPath()
		);

		$this->assertEquals(
			"BooksHandler@options",
			$route->getHandler()
		);
	}

	public function test_resource_adds_routes(): void
	{
		$router = new Router;
		$router->group(
			namespace: "App\\Http\\v1\\Handlers",
			prefix: "v1",
			routes: function(Router $router): void {
				$router->resource("widgets");
			},
		);

		$routes = $router->getRoutes();

		$this->assertEquals(
			["GET", "HEAD"],
			$routes["GET"][0]->getMethods()
		);

		$this->assertEquals(
			"v1/widgets",
			$routes["GET"][0]->getPath(),
		);

		$this->assertEquals(
			"\\App\\Http\\v1\\Handlers\\WidgetsHandler@list",
			$routes["GET"][0]->getHandler()
		);

		$this->assertSame(
			$routes["GET"][0],
			$routes["HEAD"][0]
		);


		$this->assertEquals(
			["POST"],
			$routes["POST"][0]->getMethods()
		);

		$this->assertEquals(
			"v1/widgets",
			$routes["POST"][0]->getPath(),
		);

		$this->assertEquals(
			"\\App\\Http\\v1\\Handlers\\WidgetsHandler@create",
			$routes["POST"][0]->getHandler()
		);


		$this->assertEquals(
			["GET", "HEAD"],
			$routes["GET"][1]->getMethods()
		);

		$this->assertEquals(
			"v1/widgets/{id:uuid}",
			$routes["GET"][1]->getPath(),
		);

		$this->assertEquals(
			"\\App\\Http\\v1\\Handlers\\WidgetsHandler@get",
			$routes["GET"][1]->getHandler()
		);

		$this->assertSame(
			$routes["GET"][1],
			$routes["HEAD"][1]
		);


		$this->assertEquals(
			["PUT"],
			$routes["PUT"][0]->getMethods()
		);

		$this->assertEquals(
			"v1/widgets/{id:uuid}",
			$routes["PUT"][0]->getPath(),
		);

		$this->assertEquals(
			"\\App\\Http\\v1\\Handlers\\WidgetsHandler@update",
			$routes["PUT"][0]->getHandler()
		);

		$this->assertEquals(
			["DELETE"],
			$routes["DELETE"][0]->getMethods()
		);

		$this->assertEquals(
			"v1/widgets/{id:uuid}",
			$routes["DELETE"][0]->getPath(),
		);

		$this->assertEquals(
			"\\App\\Http\\v1\\Handlers\\WidgetsHandler@delete",
			$routes["DELETE"][0]->getHandler()
		);
	}

	public function test_group(): void
	{
		$router = new Router;
		$router->group(
			scheme: "https",
			hostnames: ["example.org"],
			prefix: "v1",
			namespace: "App\\Handlers",
			middleware: [
				"App\\Middleware\\MiddlewareLayer1"
			],
			routes: function(Router $router): void {
				$router->get("/books", "BooksHandler@all");
			}
		);

		$reflectionClass = new ReflectionClass($router);
		$reflectionProperty = $reflectionClass->getProperty("routes");
		$reflectionProperty->setAccessible(true);
		$routes = $reflectionProperty->getValue($router)["GET"];

		$this->assertEquals("https", $routes[0]->getScheme());
		$this->assertEquals(["example.org"], $routes[0]->getHostnames());
		$this->assertEquals("\\App\\Handlers\\BooksHandler@all", $routes[0]->getHandler());
		$this->assertEquals("v1/books", $routes[0]->getPath());
		$this->assertEquals([
			"App\\Middleware\\MiddlewareLayer1"
		], $routes[0]->getMiddleware());
	}

	public function test_group_nested(): void
	{
		$router = new Router;
		$router->group(
			scheme: "https",
			hostnames: ["example.org"],
			prefix: "v1",
			namespace: "App\\Handlers",
			middleware: [
				"App\\Middleware\\MiddlewareLayer1"
			],
			routes: function(Router $router): void {
				$router->group(
					scheme: "http",
					hostnames: ["sub.example.org"],
					prefix: "v2",
					namespace: "App\\Handlers\\v2",
					middleware: [
						"App\\Middleware\\MiddlewareLayer2"
					],
					routes: function(Router $router): void {
						$router->get("/books", "BooksHandler@all");
					}
				);
			}
		);

		$reflectionClass = new ReflectionClass($router);
		$reflectionProperty = $reflectionClass->getProperty("routes");
		$reflectionProperty->setAccessible(true);
		$routes = $reflectionProperty->getValue($router)["GET"];

		$this->assertEquals("http", $routes[0]->getScheme());
		$this->assertEquals(["sub.example.org"], $routes[0]->getHostnames());
		$this->assertEquals("v2/books", $routes[0]->getPath());
		$this->assertEquals("\\App\\Handlers\\v2\\BooksHandler@all", $routes[0]->getHandler());
		$this->assertEquals([
			"App\\Middleware\\MiddlewareLayer1",
			"App\\Middleware\\MiddlewareLayer2"
		], $routes[0]->getMiddleware());
	}

	public function test_nested_groups_inherit_from_parent(): void
	{
		$router = new Router;
		$router->group(
			scheme: "https",
			hostnames: ["example.org"],
			prefix: "v1",
			namespace: "App\\Handlers\\v1",
			middleware: [
				"App\\Middleware\\MiddlewareLayer1"
			],
			routes: function(Router $router): void {
				$router->group(
					routes: function(Router $router): void {
						$router->get("/books", "BooksHandler@all");
					}
				);
			}
		);

		$reflectionClass = new ReflectionClass($router);
		$reflectionProperty = $reflectionClass->getProperty("routes");
		$reflectionProperty->setAccessible(true);
		$routes = $reflectionProperty->getValue($router)["GET"];

		$this->assertEquals("https", $routes[0]->getScheme());
		$this->assertEquals(["example.org"], $routes[0]->getHostnames());
		$this->assertEquals("v1/books", $routes[0]->getPath());
		$this->assertEquals("\\App\\Handlers\\v1\\BooksHandler@all", $routes[0]->getHandler());
		$this->assertEquals([
			"App\\Middleware\\MiddlewareLayer1"
		], $routes[0]->getMiddleware());
	}
}