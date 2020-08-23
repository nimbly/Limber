<?php

namespace Limber\Tests;

use Capsule\ServerRequest;
use Limber\Router\Engines\FlatRouter as Router;
use Limber\Router\Route;
use PHPUnit\Framework\TestCase;

/**
 * @covers Limber\Router\Route
 * @covers Limber\Router\Engines\FlatRouter
 */
class FlatRouterTest extends TestCase
{
    public function test_constructor(): void
    {
        $routeManager = new Router([
            new Route("get", "books", "BooksController@all"),
            new Route("get", "books/{id}", "BooksController@get"),
            new Route("post", "books", "BooksController@create")
        ]);

        $this->assertNotEmpty(
            $routeManager->resolve(new ServerRequest('get', 'books'))
        );

        $this->assertNotEmpty(
            $routeManager->resolve(new ServerRequest('get', 'books/123'))
        );

        $this->assertNotEmpty(
            $routeManager->resolve(new ServerRequest('post', 'books'))
        );
    }

    public function test_add_route(): void
    {
        $routeManager = new Router;
        $route = $routeManager->add(["get", "post"], "books/edit", "BooksController@edit");

        $this->assertEquals(["GET", "POST"], $route->getMethods());
        $this->assertEquals("books/edit", $route->getPath());
        $this->assertEquals("BooksController@edit", $route->getHandler());
    }

    public function test_resolve(): void
    {
        $routeManager = new Router([
            new Route("get", "books/{id}", "BooksController@get"),
            new Route("post", "books", "BooksController@create"),

            new Route("get", "authors/{id}", "AuthorsController@get"),
            new Route("post", "authors", "AuthorsController@create")
        ]);

        $route = $routeManager->resolve(
            new ServerRequest('get', 'https://example.com/authors/1234')
        );

		$this->assertNotNull($route);
        $this->assertEquals(["GET"], $route->getMethods());
        $this->assertEquals("AuthorsController@get", $route->getHandler());
    }

    public function test_get_methods(): void
    {
        $routeManager = new Router([
            new Route("get", "books/{id}", "BooksController@get"),
            new Route("patch", "books/{id}", "BooksController@update"),
            new Route("delete", "books/{id}", "BooksController@delete"),
            new Route("post", "books", "BooksController@create"),

            new Route("get", "authors/{id}", "AuthorsController@get"),
            new Route("post", "authors", "AuthorsController@create")
        ]);

        $methods = $routeManager->getMethods(
            new ServerRequest('get', "https://example.com/books/1234")
        );

        $this->assertEquals(["GET", "PATCH", "DELETE"], $methods);
    }
}