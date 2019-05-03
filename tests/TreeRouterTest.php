<?php

namespace Limber\Tests\Router;

use Limber\Router\Route;
use Limber\Router\TreeRouter as Router;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers Limber\Router\TreeRouter
 * @covers Limber\Router\RouterAbstract
 * @covers Limber\Router\Route
 * @covers Limber\Router\RouteBranch
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
            $router->resolve(Request::create('books'))
        );

        $this->assertNotEmpty(
            $router->resolve(Request::create('books/123'))
        );

        $this->assertNotEmpty(
            $router->resolve(Request::create('books', 'post'))
        );
    }

    public function test_add_route()
    {
        $router = new Router;
        $route = $router->add(["get", "post"], "books/edit", "BooksController@edit");

        $this->assertEquals(["GET", "POST"], $route->getMethods());
        $this->assertEquals("books/edit", $route->getUri());
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
            Request::create("https://example.com/authors/1234")
        );

        $this->assertEquals(["GET"], $route->getMethods());
        $this->assertEquals("AuthorsController@get", $route->getAction());
    }

    public function test_get_methods_for_uri()
    {
        $router = new Router([
            new Route("get", "books/{id}", "BooksController@get"),
            new Route("patch", "books/{id}", "BooksController@update"),
            new Route("delete", "books/{id}", "BooksController@delete"),
            new Route("post", "books", "BooksController@create"),
            
            new Route("get", "authors/{id}", "AuthorsController@get"),
            new Route("post", "authors", "AuthorsController@create")
        ]);

        $methods = $router->getMethodsForUri(
            Request::create("https://example.com/books/1234")
        );

        $this->assertEquals(["GET", "PATCH", "DELETE"], $methods);
    }
}