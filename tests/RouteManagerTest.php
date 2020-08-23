<?php

namespace Limber\Tests;

use Limber\RouteManager;
use PHPUnit\Framework\TestCase;

/**
 * @covers Limber\Router\Router
 * @covers Limber\Router\Engines\DefaultRouter
 * @covers Limber\Router\Route
 */
class RouteManagerTest extends TestCase
{
    public function test_set_pattern(): void
    {
        RouteManager::setPattern("isbn", "\d{3}\-\d\-\d{3}\-\d{5}\-\d");
        $this->assertEquals(RouteManager::getPattern("isbn"), "\d{3}\-\d\-\d{3}\-\d{5}\-\d");
	}

	public function test_get_pattern_not_found(): void
	{
		$this->assertNull(RouteManager::getPattern("ean13"));
	}

    public function test_adding_get_route(): void
    {
        $routeManager = new RouteManager;
        $route = $routeManager->get("books/{id}", "BooksController@get");
        $this->assertEquals(["GET", "HEAD"], $route->getMethods());
    }

    public function test_adding_post_route(): void
    {
        $routeManager = new RouteManager;
        $route = $routeManager->post("books", "BooksController@post");

        $this->assertEquals(["POST"], $route->getMethods());
    }

    public function test_adding_put_route(): void
    {
        $routeManager = new RouteManager;
        $route = $routeManager->put("books", "BooksController@put");

        $this->assertEquals(["PUT"], $route->getMethods());
    }

    public function test_adding_patch_route(): void
    {
        $routeManager = new RouteManager;
        $route = $routeManager->patch("books", "BooksController@patch");

        $this->assertEquals(["PATCH"], $route->getMethods());
    }

    public function test_adding_delete_route(): void
    {
        $routeManager = new RouteManager;
        $route = $routeManager->delete("books", "BooksController@delete");

        $this->assertEquals(["DELETE"], $route->getMethods());
    }

    public function test_adding_head_route(): void
    {
        $routeManager = new RouteManager;
        $route = $routeManager->head("books", "BooksController@head");

        $this->assertEquals(["HEAD"], $route->getMethods());
    }

    public function test_adding_options_route(): void
    {
        $routeManager = new RouteManager;
        $route = $routeManager->options("books", "BooksController@options");

        $this->assertEquals(["OPTIONS"], $route->getMethods());
	}

	public function test_group(): void
	{
		$routeManager = new RouteManager;
		$routeManager->group([
			"scheme" => "https",
			"hostname" => "example.org",
			"prefix" => "v1",
			"namespace" => "App\\Controllers",
			"middleware" => [
				"App\\Middleware\\MiddlewareLayer1"
			]
		], function(RouteManager $routeManager): void {
			$routeManager->get("/books", "BooksController@all");
		});

		$routes = $routeManager->getRoutes();

		$this->assertEquals(["https"], $routes[0]->getSchemes());
		$this->assertEquals(["example.org"], $routes[0]->getHostnames());
		$this->assertEquals("v1", $routes[0]->getPrefix());
		$this->assertEquals("App\\Controllers", $routes[0]->getNamespace());
		$this->assertEquals([
			"App\\Middleware\\MiddlewareLayer1"
		], $routes[0]->getMiddleware());
	}

	public function test_group_nested(): void
	{
		$routeManager = new RouteManager;
		$routeManager->group([
			"scheme" => "https",
			"hostname" => "example.org",
			"prefix" => "v1",
			"namespace" => "App\\Controllers",
			"middleware" => [
				"App\\Middleware\\MiddlewareLayer1"
			]
		], function(RouteManager $routeManager): void {

			$routeManager->group([
				"scheme" => "http",
				"hostname" => "sub.example.org",
				"prefix" => "v2",
				"namespace" => "App\\Controllers\\v2",
				"middleware" => [
					"App\\Middleware\\MiddlewareLayer2"
				]
			], function(RouteManager $routeManager): void {
				$routeManager->get("/books", "BooksController@all");
			});

		});

		$routes = $routeManager->getRoutes();

		$this->assertEquals(["http"], $routes[0]->getSchemes());
		$this->assertEquals(["sub.example.org"], $routes[0]->getHostnames());
		$this->assertEquals("v2", $routes[0]->getPrefix());
		$this->assertEquals("App\\Controllers\\v2", $routes[0]->getNamespace());
		$this->assertEquals([
			"App\\Middleware\\MiddlewareLayer1",
			"App\\Middleware\\MiddlewareLayer2"
		], $routes[0]->getMiddleware());
	}

	public function test_merge_group_config(): void
	{
		$routeManager = new RouteManager;

		$reflection = new \ReflectionClass($routeManager);
		$method = $reflection->getMethod('mergeGroupConfig');
		$method->setAccessible(true);

		$config = $method->invokeArgs($routeManager, [
			[
				"hostname" => "example.org",
				"prefix" => "v1",
				"namespace" => "App\Controller",
				"middleware" => [
					"App\Middleware\MiddlewareLayer1"
				],
				"attributes" => [
					"Attribute1" => "Value1"
				]
			],
			[
				"hostname" => "sub.example.org",
				"prefix" => "v2",
				"namespace" => "App\\v2\\Controller",
				"middleware" => [
					"App\Middleware\MiddlewareLayer2"
				],
				"attributes" => [
					"Attribute2" => "Value2"
				]
			]
		]);

		$this->assertEquals([
			"scheme" => null,
			"hostname" => "sub.example.org",
			"prefix" => "v2",
			"namespace" => "App\\v2\\Controller",
			"middleware" => [
				"App\Middleware\MiddlewareLayer1",
				"App\Middleware\MiddlewareLayer2"
			],
			"attributes" => [
				"Attribute1" => "Value1",
				"Attribute2" => "Value2"
			]
		], $config);
	}

	public function test_nested_groups_inherit_from_parent(): void
	{
		$routeManager = new RouteManager;
		$routeManager->group([
			"scheme" => "https",
			"hostname" => "example.org",
			"prefix" => "v1",
			"namespace" => "App\\Controllers\\v1",
			"middleware" => [
				"App\\Middleware\\MiddlewareLayer1"
			],
			"attributes" => [

			]
		], function(RouteManager $routeManager): void {

			$routeManager->group([], function(RouteManager $routeManager): void {
				$routeManager->get("/books", "BooksController@all");
			});

		});

		$routes = $routeManager->getRoutes();

		$this->assertEquals(["https"], $routes[0]->getSchemes());
		$this->assertEquals(["example.org"], $routes[0]->getHostnames());
		$this->assertEquals("v1", $routes[0]->getPrefix());
		$this->assertEquals("App\\Controllers\\v1", $routes[0]->getNamespace());
		$this->assertEquals([
			"App\\Middleware\\MiddlewareLayer1"
		], $routes[0]->getMiddleware());
		$this->assertEquals(
			[],
			$routes[0]->getAttributes()
		);
	}
}