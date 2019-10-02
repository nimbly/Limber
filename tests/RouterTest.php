<?php

namespace Limber\Tests;

use Limber\Router\Router;
use PHPUnit\Framework\TestCase;

/**
 * @covers Limber\Router\Router
 * @covers Limber\Router\Engines\DefaultRouter
 * @covers Limber\Router\Route
 */
class RouterTest extends TestCase
{
    public function test_set_pattern()
    {
        Router::setPattern("isbn", "\d{3}\-\d\-\d{3}\-\d{5}\-\d");
        $this->assertEquals(Router::getPattern("isbn"), "\d{3}\-\d\-\d{3}\-\d{5}\-\d");
	}

	public function test_get_pattern_not_found()
	{
		$this->assertNull(Router::getPattern("ean13"));
	}

    public function test_adding_get_route()
    {
        $router = new Router;
        $route = $router->get("books/{id}", "BooksController@get");
        $this->assertEquals(["GET", "HEAD"], $route->getMethods());
    }

    public function test_adding_post_route()
    {
        $router = new Router;
        $route = $router->post("books", "BooksController@post");

        $this->assertEquals(["POST"], $route->getMethods());
    }

    public function test_adding_put_route()
    {
        $router = new Router;
        $route = $router->put("books", "BooksController@put");

        $this->assertEquals(["PUT"], $route->getMethods());
    }

    public function test_adding_patch_route()
    {
        $router = new Router;
        $route = $router->patch("books", "BooksController@patch");

        $this->assertEquals(["PATCH"], $route->getMethods());
    }

    public function test_adding_delete_route()
    {
        $router = new Router;
        $route = $router->delete("books", "BooksController@delete");

        $this->assertEquals(["DELETE"], $route->getMethods());
    }

    public function test_adding_head_route()
    {
        $router = new Router;
        $route = $router->head("books", "BooksController@head");

        $this->assertEquals(["HEAD"], $route->getMethods());
    }

    public function test_adding_options_route()
    {
        $router = new Router;
        $route = $router->options("books", "BooksController@options");

        $this->assertEquals(["OPTIONS"], $route->getMethods());
	}

	public function test_group()
	{
		$router = new Router;
		$router->group([
			"scheme" => "https",
			"hostname" => "example.org",
			"prefix" => "v1",
			"namespace" => "App\\Controllers",
			"middleware" => [
				"App\\Middleware\\MiddlewareLayer1"
			]
		], function($router){
			$router->get("/books", "BooksController@all");
		});

		$routes = $router->getRoutes();

		$this->assertEquals(["https"], $routes[0]->getSchemes());
		$this->assertEquals(["example.org"], $routes[0]->getHostnames());
		$this->assertEquals("v1", $routes[0]->getPrefix());
		$this->assertEquals("App\\Controllers", $routes[0]->getNamespace());
		$this->assertEquals([
			"App\\Middleware\\MiddlewareLayer1"
		], $routes[0]->getMiddleware());
	}

	public function test_group_nested()
	{
		$router = new Router;
		$router->group([
			"scheme" => "https",
			"hostname" => "example.org",
			"prefix" => "v1",
			"namespace" => "App\\Controllers",
			"middleware" => [
				"App\\Middleware\\MiddlewareLayer1"
			]
		], function($router){

			$router->group([
				"scheme" => "http",
				"hostname" => "sub.example.org",
				"prefix" => "v2",
				"namespace" => "App\\Controllers\\v2",
				"middleware" => [
					"App\\Middleware\\MiddlewareLayer2"
				]
			], function($router){
				$router->get("/books", "BooksController@all");
			});

		});

		$routes = $router->getRoutes();

		$this->assertEquals(["http"], $routes[0]->getSchemes());
		$this->assertEquals(["sub.example.org"], $routes[0]->getHostnames());
		$this->assertEquals("v2", $routes[0]->getPrefix());
		$this->assertEquals("App\\Controllers\\v2", $routes[0]->getNamespace());
		$this->assertEquals([
			"App\\Middleware\\MiddlewareLayer1",
			"App\\Middleware\\MiddlewareLayer2"
		], $routes[0]->getMiddleware());
	}

	public function test_merge_group_config()
	{
		$router = new Router;

		$reflection = new \ReflectionClass($router);
		$method = $reflection->getMethod('mergeGroupConfig');
		$method->setAccessible(true);

		$config = $method->invokeArgs($router, [
			[
				"hostname" => "example.org",
				"prefix" => "v1",
				"namespace" => "App\Controller",
				"middleware" => [
					"App\Middleware\MiddlewareLayer1"
				],
			],
			[
				"hostname" => "sub.example.org",
				"prefix" => "v2",
				"namespace" => "App\\v2\\Controller",
				"middleware" => [
					"App\Middleware\MiddlewareLayer2"
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
			]
		], $config);
	}

	public function test_nested_groups_inherit_from_parent()
	{
		$router = new Router;
		$router->group([
			"scheme" => "https",
			"hostname" => "example.org",
			"prefix" => "v1",
			"namespace" => "App\\Controllers\\v1",
			"middleware" => [
				"App\\Middleware\\MiddlewareLayer1"
			]
		], function($router){

			$router->group([], function($router){
				$router->get("/books", "BooksController@all");
			});

		});

		$routes = $router->getRoutes();

		$this->assertEquals(["https"], $routes[0]->getSchemes());
		$this->assertEquals(["example.org"], $routes[0]->getHostnames());
		$this->assertEquals("v1", $routes[0]->getPrefix());
		$this->assertEquals("App\\Controllers\\v1", $routes[0]->getNamespace());
		$this->assertEquals([
			"App\\Middleware\\MiddlewareLayer1"
		], $routes[0]->getMiddleware());
	}
}