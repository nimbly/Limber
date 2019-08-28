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

    public function test_adding_get_route()
    {
        $router = new Router;
        $route = $router->get("books/{id}", "BooksController@get");
        $this->assertEquals(["GET"], $route->getMethods());
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
}