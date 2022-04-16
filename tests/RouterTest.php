<?php

namespace Limber\Tests;

use Nimbly\Limber\Router\Router;
use PHPUnit\Framework\TestCase;

/**
 * @covers Nimbly\Limber\Router\Router
 * @covers Nimbly\Limber\Router\Route
 *
 * @uses Nimbly\Limber\Router\RouterInterface
 */
class RouterTest extends TestCase
{
	public function test_set_pattern(): void
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

		$routes = $router->getRoutes();

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

		$routes = $router->getRoutes();

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

		$routes = $router->getRoutes();

		$this->assertEquals("https", $routes[0]->getScheme());
		$this->assertEquals(["example.org"], $routes[0]->getHostnames());
		$this->assertEquals("v1/books", $routes[0]->getPath());
		$this->assertEquals("\\App\\Handlers\\v1\\BooksHandler@all", $routes[0]->getHandler());
		$this->assertEquals([
			"App\\Middleware\\MiddlewareLayer1"
		], $routes[0]->getMiddleware());
	}
}