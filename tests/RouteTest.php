<?php

namespace Limber\Tests;

use Nimbly\Limber\Router\Route;
use PHPUnit\Framework\TestCase;

/**
 * @covers Nimbly\Limber\Router\Route
 * @covers Nimbly\Limber\Router\Router
 */
class RouteTest extends TestCase
{
	public function test_constructor(): void
	{
		$route = new Route(
			methods: ["get", "post"],
			path: "v1/books/{id}",
			handler: "Handlers\\BooksController@edit",
			scheme: "https",
			hostnames: ["localhost"],
			middleware: [
				"App\Middleware\SomeMiddleware",
			],
			attributes: [
				"attr1" => "value1"
			]
		);

		$this->assertEquals(["GET", "POST"], $route->getMethods());
		$this->assertEquals("v1/books/{id}", $route->getPath());
		$this->assertEquals("Handlers\BooksController@edit", $route->getHandler());
		$this->assertEquals("https", $route->getScheme());
		$this->assertEquals(["localhost"], $route->getHostnames());
		$this->assertEquals(["App\Middleware\SomeMiddleware"], $route->getMiddleware());
		$this->assertEquals(["attr1" => "value1"], $route->getAttributes());
	}

	public function test_get_path_params(): void
	{
		$route = new Route(
			methods: ["get"],
			path: "/^books\/(?<bookId>.+)\/comments\/(?<commentId>.+)$/",
			handler: "BooksController@get"
		);

		$params = $route->getPathParameters("books/1234/comments/5678");

		$this->assertEquals([
			"bookId" => 1234,
			"commentId" => 5678
		], $params);
	}

	public function test_match_single_scheme(): void
	{
		$route = new Route(
			methods: ["get"],
			path: "books",
			handler: "BooksController@get",
			scheme: "http"
		);

		$this->assertTrue($route->matchScheme("http"));
	}

	public function test_match_multiple_schemes(): void
	{
		$route = new Route(
			methods: ["get"],
			path: "books",
			handler: "BooksController@get"
		);

		$this->assertTrue($route->matchScheme("http"));
		$this->assertTrue($route->matchScheme("https"));
	}

	public function test_non_matching_scheme(): void
	{
		$route = new Route(
			methods: ["get"],
			path: "books",
			handler: "BooksController@get",
			scheme: "http"
		);

		$this->assertFalse($route->matchScheme("https"));
	}

	public function test_match_single_hostname(): void
	{
		$route = new Route(
			methods: ["get"],
			path: "books",
			handler: "BooksController@get",
			hostnames: ["localhost"]
		);

		$this->assertTrue($route->matchHostname("localhost"));
	}

	public function test_match_multiple_hostnames(): void
	{
		$route = new Route(
			methods: ["get"],
			path: "books",
			handler: "BooksController@get",
			hostnames: ["localhost", "api.localhost"]
		);

		$this->assertTrue($route->matchHostname("localhost"));
		$this->assertTrue($route->matchHostname("api.localhost"));
	}

	public function test_non_matching_hostname(): void
	{
		$route = new Route(
			methods: ["get"],
			path: "books",
			handler: "BooksController@get",
			hostnames: ["localhost"]
		);

		$this->assertFalse($route->matchHostname("api.localhost"));
	}

	public function test_match_single_method(): void
	{
		$route = new Route(
			methods: ["get"],
			path: "books",
			handler: "BooksController@get"
		);

		$this->assertTrue($route->matchMethod("get"));
	}

	public function test_match_multiple_methods(): void
	{
		$route = new Route(
			methods: ["get", "post"],
			path: "books",
			handler: "BooksController@get"
		);

		$this->assertTrue($route->matchMethod("get"));
		$this->assertTrue($route->matchMethod("post"));
	}

	public function test_non_matching_method(): void
	{
		$route = new Route(
			methods: ["get"],
			path: "books",
			handler: "BooksController@get"
		);

		$this->assertFalse($route->matchMethod("post"));
	}

	public function test_match_path(): void
	{
		$route = new Route(
			methods: ["get"],
			path: "/^books\/(?<bookId>.+)\/comments\/(?<commentId>.+)$/",
			handler: "BooksController@get"
		);

		$this->assertTrue($route->matchPath("books/1234/comments/5678"));
	}

	public function test_match_path_with_pattern(): void
	{
		$route = new Route(
			methods: ["get"],
			path: "/^books\/(?<bookId>\d+)\/comments\/(?<commentId>[a-f0-9]+)$/",
			handler: "BooksController@get"
		);

		$this->assertTrue($route->matchPath("books/1234/comments/a5f9"));
	}

	public function test_match_path_with_pattern_fails(): void
	{
		$route = new Route(
			methods: ["get"],
			path: "/^books\/(?<bookId>\d+)\/comments$/",
			handler: "BooksController@get"
		);

		$this->assertFalse($route->matchPath("books/book-23"));
	}

	public function test_non_matching_path(): void
	{
		$route = new Route(
			methods: ["get"],
			path: "/^books\/(?<bookId>.+)\/comments\/(?<commentId>.+)$/",
			handler: "BooksController@get"
		);

		$this->assertFalse($route->matchPath("books/1234"));
	}

	public function test_get_attribute(): void
	{
		$route = new Route(
			methods: ["get"],
			path: "/",
			handler: "BooksHandler@get",
			attributes: [
				"attr" => "value"
			]
		);

		$this->assertEquals("value", $route->getAttribute("attr"));
	}

	public function test_get_attributes(): void
	{
		$route = new Route(
			methods: ["get"],
			path: "/",
			handler: "BooksHandler@get",
			attributes: [
				"attr" => "value",
				"attr2" => "value"
			]
		);

		$this->assertEquals([
			"attr" => "value",
			"attr2" => "value"
		], $route->getAttributes());
	}

	public function test_has_attribute(): void
	{
		$route = new Route(
			methods: ["get"],
			path: "/",
			handler: "BooksHandler@get",
			attributes: [
				"attr" => "value"
			]
		);

		$this->assertTrue($route->hasAttribute("attr"));
		$this->assertFalse($route->hasAttribute("attr2"));
	}
}