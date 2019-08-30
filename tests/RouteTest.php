<?php

namespace Limber\Tests;

use Capsule\Response;
use Capsule\ResponseStatus;
use Limber\Router\Route;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * @covers Limber\Router\Route
 * @covers ::class_method
 */
class RouteTest extends TestCase
{
    public function test_constructor()
    {
        $route = new Route(
            ["get", "post"],
            "books/{id}",
            "BooksController@edit",
            [
                'scheme' => 'https',
                'hostname' => 'localhost',
                'prefix' => 'v1',
                'namespace' => 'Controllers',
                'middleware' => [
                    "App\Middleware\SomeMiddleware",
                ],
            ]
        );

        $this->assertTrue(\in_array("POST", $route->getMethods()));
        $this->assertEquals("v1/books/{id}", $route->getPath());
        $this->assertEquals("Controllers\BooksController@edit", $route->getAction());

        $this->assertTrue(\in_array("https", $route->getSchemes()));
        $this->assertTrue(\in_array("localhost", $route->getHostnames()));
        $this->assertEquals("v1", $route->getPrefix());
        $this->assertEquals("Controllers", $route->getNamespace());
        $this->assertEquals(["App\Middleware\SomeMiddleware"], $route->getMiddleware());
    }

    public function test_set_schemes_works_with_string()
    {
        $route = new Route("get", "books", "BooksController@get");
        $route->setSchemes("https");
        $this->assertEquals(["https"], $route->getSchemes());
    }

    public function test_set_schemes_works_with_array()
    {
        $route = new Route("get", "books", "BooksController@get");
        $route->setSchemes(["http", "https"]);
        $this->assertEquals(["http", "https"], $route->getSchemes());
    }

    public function test_set_hostnames_works_with_string()
    {
        $route = new Route("get", "books", "BooksController@get");
        $route->setHostnames("localhost");
        $this->assertEquals(["localhost"], $route->getHostnames());
    }

    public function test_set_hostnames_works_with_array()
    {
        $route = new Route("get", "books", "BooksController@get");
        $route->setHostnames(["localhost", "api.localhost"]);
        $this->assertEquals(["localhost", "api.localhost"], $route->getHostnames());
    }

    public function test_set_prefix()
    {
        $route = new Route("get", "books", "BooksController@get");
        $route->setPrefix("v1");
        $this->assertEquals("v1", $route->getPrefix());
        $this->assertEquals("v1/books", $route->getPath());
    }

    public function test_set_middleware_as_string()
    {
        $route = new Route("get", "books", "BooksController@get");
        $route->setMiddleware("Middleware\MyMiddlewareClass");
        $this->assertEquals(["Middleware\MyMiddlewareClass"], $route->getMiddleware());
    }

    public function test_set_middleware_as_array()
    {
        $route = new Route("get", "books", "BooksController@get");
        $route->setMiddleware(["Middleware\MyMiddlewareClass"]);
        $this->assertEquals(["Middleware\MyMiddlewareClass"], $route->getMiddleware());
    }

    public function test_set_namespace()
    {
        $route = new Route("get", "books", "BooksController@get");
        $route->setNamespace("Controllers");
        $this->assertEquals("Controllers", $route->getNamespace());
        $this->assertEquals("Controllers\BooksController@get", $route->getAction());
    }

    public function test_get_path_params()
    {
        $route = new Route("get", "books/{bookId}/comments/{commentId}", "BooksController@get");
        $params = $route->getPathParams("books/1234/comments/5678");

        $this->assertEquals([
            "bookId" => 1234,
            "commentId" => 5678
        ], $params);
    }

    public function test_match_single_scheme()
    {
        $route = new Route("get", "books/{bookId}/comments/{commentId}", "BooksController@get");
        $route->setSchemes("http");
        $this->assertTrue($route->matchScheme("http"));
    }

    public function test_match_multiple_schemes()
    {
        $route = new Route("get", "books/{bookId}/comments/{commentId}", "BooksController@get");
        $route->setSchemes(["http", "https"]);
        $this->assertTrue($route->matchScheme("http"));
        $this->assertTrue($route->matchScheme("https"));
    }

    public function test_non_matching_scheme()
    {
        $route = new Route("get", "books/{bookId}/comments/{commentId}", "BooksController@get");
        $route->setSchemes("http");
        $this->assertFalse($route->matchScheme("https"));
    }

    public function test_match_single_hostname()
    {
        $route = new Route("get", "books/{bookId}/comments/{commentId}", "BooksController@get");
        $route->setHostnames("localhost");
        $this->assertTrue($route->matchHostname("localhost"));
    }

    public function test_match_multiple_hostnames()
    {
        $route = new Route("get", "books/{bookId}/comments/{commentId}", "BooksController@get");
        $route->setHostnames(["localhost", "api.localhost"]);
        $this->assertTrue($route->matchHostname("localhost"));
        $this->assertTrue($route->matchHostname("api.localhost"));
    }

    public function test_non_matching_hostname()
    {
        $route = new Route("get", "books/{bookId}/comments/{commentId}", "BooksController@get");
        $route->setHostnames("localhost");
        $this->assertFalse($route->matchHostname("api.localhost"));
    }

    public function test_match_single_method()
    {
        $route = new Route("get", "books/{bookId}/comments/{commentId}", "BooksController@get");
        $this->assertTrue($route->matchMethod("get"));
    }

    public function test_match_multiple_methods()
    {
        $route = new Route(["get", "post"], "books/{bookId}/comments/{commentId}", "BooksController@get");
        $this->assertTrue($route->matchMethod("get"));
        $this->assertTrue($route->matchMethod("post"));
    }

    public function test_non_matching_method()
    {
        $route = new Route("get", "books/{bookId}/comments/{commentId}", "BooksController@get");
        $this->assertFalse($route->matchMethod("post"));
    }

    public function test_match_path()
    {
        $route = new Route("get", "books/{bookId}/comments/{commentId}", "BooksController@get");
        $this->assertTrue($route->matchPath("books/1234/comments/5678"));
    }

    public function test_non_matching_path()
    {
        $route = new Route("get", "books/{bookId}/comments/{commentId}", "BooksController@get");
        $this->assertFalse($route->matchPath("books/1234"));
	}

	public function test_get_callable_action_string()
	{
		$route = new Route("get", "books/{bookId}/comments/{commentId}", static::class . "@test_get_callable_action_string");
		$this->assertTrue(
			\is_callable($route->getCallableAction())
		);
	}

	public function test_get_callable_action_unresolvable()
	{
		$route = new Route("get", "/books", new \StdClass);

		$this->expectException(Throwable::class);
		$route->getCallableAction();
	}

	public function test_get_callable_action_closure()
	{
		$handler = function(ServerRequestInterface $request){
			return new Response(
				ResponseStatus::OK,
				"OK"
			);
		};

		$route = new Route("get", "/books", $handler);

		$this->assertSame(
			$handler,
			$route->getCallableAction()
		);
	}
}