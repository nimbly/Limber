<?php

namespace Limber\Tests;

use Capsule\ServerRequest;
use Limber\Router\Route;
use Limber\Router\Engines\TreeRouter as Router;
use PHPUnit\Framework\TestCase;

/**
 * @covers Limber\Router\Engines\TreeRouter
 * @covers Limber\Router\Router
 * @covers Limber\Router\Route
 * @covers Limber\Router\Engines\RouteBranch
 */
class TreeRouterTest extends TestCase
{
    public function test_constructor()
    {
        $router = new Router([
            new Route("get", "books", "BooksController@all"),
            new Route("get", "books/{id}", "BooksController@get"),
            new Route("post", "books", "BooksController@create")
        ]);

        $this->assertNotEmpty(
            $router->resolve(ServerRequest::create('get', 'books', null, [], [], [], []))
        );

        $this->assertNotEmpty(
            $router->resolve(ServerRequest::create('get', 'books/123', null, [], [], [], []))
        );

        $this->assertNotEmpty(
            $router->resolve(ServerRequest::create('post', 'books', null, [], [], [], []))
        );
    }

    public function test_add_route()
    {
        $router = new Router;
        $route = $router->add(["get", "post"], "books/edit", "BooksController@edit");

        $this->assertEquals(["GET", "POST"], $route->getMethods());
        $this->assertEquals("books/edit", $route->getPath());
        $this->assertEquals("BooksController@edit", $route->getAction());
    }

    public function test_resolve()
    {
        $router = new Router([
            new Route("get", "books/{id}", "BooksController@get"),
            new Route("post", "books", "BooksController@create"),

            new Route("get", "authors/{id}", "AuthorsController@get"),
            new Route("post", "authors", "AuthorsController@create")
        ]);

        $route = $router->resolve(
            ServerRequest::create("get", "https://example.com/authors/1234", null, [], [], [], [])
        );

        $this->assertEquals(["GET"], $route->getMethods());
        $this->assertEquals("AuthorsController@get", $route->getAction());
    }

    public function test_get_methods()
    {
        $router = new Router([
            new Route("get", "books/{id}", "BooksController@get"),
            new Route("patch", "books/{id}", "BooksController@update"),
            new Route("delete", "books/{id}", "BooksController@delete"),
            new Route("post", "books", "BooksController@create"),

            new Route("get", "authors/{id}", "AuthorsController@get"),
            new Route("post", "authors", "AuthorsController@create")
        ]);

        $methods = $router->getMethods(
            ServerRequest::create("get", "https://example.com/books/1234", null, [], [], [], [])
        );

        $this->assertEquals(["GET", "PATCH", "DELETE"], $methods);
    }
}