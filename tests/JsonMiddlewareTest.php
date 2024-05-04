<?php

namespace Nimbly\Limber\Tests;

use Nimbly\Capsule\Response;
use Nimbly\Capsule\ResponseStatus;
use Nimbly\Capsule\ServerRequest;
use Nimbly\Limber\Exceptions\BadRequestHttpException;
use Nimbly\Limber\Exceptions\NotAcceptableHttpException;
use Nimbly\Limber\Middleware\JsonMiddleware;
use Nimbly\Limber\Middleware\RequestHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @covers Nimbly\Limber\Middleware\JsonMiddleware
 */
class JsonMiddlewareTest extends TestCase
{
	public function test_non_json_content_type_throws_not_acceptable_http_exception(): void
	{
		$body = [
			"id" => "d5adf9d7-5e55-448e-b1fd-b131c31d7ec1",
			"name" => "Limber"
		];

		$request = new ServerRequest(
			method: "post",
			uri: "/foo",
			body: \json_encode($body),
			headers: ["Content-Type" => "application/xml"]
		);

		$handler = new RequestHandler(
			function(ServerRequestInterface $request): ResponseInterface {
				return new Response(
					statusCode: ResponseStatus::OK,
					body: \json_encode([
						"id" => $request->getParsedBody()["id"],
						"name" => $request->getParsedBody()["name"]
					]),
					headers: ["Content-Type" => "application/json"]
				);
			}
		);

		$middleware = new JsonMiddleware;

		$this->expectException(NotAcceptableHttpException::class);
		$middleware->process($request, $handler);
	}

	public function test_malformed_json_content_throws_bad_request_http_exception(): void
	{
		$body = "This is not JSON";

		$request = new ServerRequest(
			method: "post",
			uri: "/foo",
			body: $body,
			headers: ["Content-Type" => "application/json"]
		);

		$handler = new RequestHandler(
			function(ServerRequestInterface $request): ResponseInterface {
				return new Response(
					statusCode: ResponseStatus::OK,
					body: \json_encode([
						"id" => $request->getParsedBody()["id"],
						"name" => $request->getParsedBody()["name"]
					]),
					headers: ["Content-Type" => "application/json"]
				);
			}
		);

		$middleware = new JsonMiddleware;
		$this->expectException(BadRequestHttpException::class);
		$middleware->process($request, $handler);
	}

	public function test_parsed_content_is_added_to_request_body(): void
	{
		$body = [
			"id" => "d5adf9d7-5e55-448e-b1fd-b131c31d7ec1",
			"name" => "Limber"
		];

		$request = new ServerRequest(
			method: "post",
			uri: "/foo",
			body: \json_encode($body),
			headers: ["Content-Type" => "application/json"]
		);

		$handler = new RequestHandler(
			function(ServerRequestInterface $request): ResponseInterface {
				return new Response(
					statusCode: ResponseStatus::OK,
					body: \json_encode($request->getParsedBody()),
					headers: ["Content-Type" => "application/json"]
				);
			}
		);

		$middleware = new JsonMiddleware;
		$response = $middleware->process($request, $handler);

		$this->assertEquals(
			$body,
			\json_decode($response->getBody()->getContents(), true)
		);
	}

	public function test_request_body_with_post(): void
	{
		$body = [
			"id" => "d5adf9d7-5e55-448e-b1fd-b131c31d7ec1",
			"name" => "Limber"
		];

		$request = new ServerRequest(
			method: "post",
			uri: "/foo",
			body: \json_encode($body),
			headers: ["Content-Type" => "application/json"]
		);

		$handler = new RequestHandler(
			function(ServerRequestInterface $request): ResponseInterface {
				return new Response(
					statusCode: ResponseStatus::OK,
					body: \json_encode([
						"id" => $request->getParsedBody()["id"],
						"name" => $request->getParsedBody()["name"]
					]),
					headers: ["Content-Type" => "application/json"]
				);
			}
		);

		$middleware = new JsonMiddleware;
		$response = $middleware->process($request, $handler);

		$parsed_response = \json_decode($response->getBody());

		$this->assertEquals(
			"d5adf9d7-5e55-448e-b1fd-b131c31d7ec1",
			$parsed_response->id
		);

		$this->assertEquals(
			"Limber",
			$parsed_response->name
		);
	}

	public function test_request_body_with_put(): void
	{
		$body = [
			"id" => "d5adf9d7-5e55-448e-b1fd-b131c31d7ec1",
			"name" => "Limber"
		];

		$request = new ServerRequest(
			method: "put",
			uri: "/foo",
			body: \json_encode($body),
			headers: ["Content-Type" => "application/json"]
		);

		$handler = new RequestHandler(
			function(ServerRequestInterface $request): ResponseInterface {
				return new Response(
					statusCode: ResponseStatus::OK,
					body: \json_encode([
						"id" => $request->getParsedBody()["id"],
						"name" => $request->getParsedBody()["name"]
					]),
					headers: ["Content-Type" => "application/json"]
				);
			}
		);

		$middleware = new JsonMiddleware;
		$response = $middleware->process($request, $handler);

		$parsed_response = \json_decode($response->getBody());

		$this->assertEquals(
			"d5adf9d7-5e55-448e-b1fd-b131c31d7ec1",
			$parsed_response->id
		);

		$this->assertEquals(
			"Limber",
			$parsed_response->name
		);
	}

	public function test_request_body_with_patch(): void
	{
		$body = [
			"id" => "d5adf9d7-5e55-448e-b1fd-b131c31d7ec1",
			"name" => "Limber"
		];

		$request = new ServerRequest(
			method: "patch",
			uri: "/foo",
			body: \json_encode($body),
			headers: ["Content-Type" => "application/json"]
		);

		$handler = new RequestHandler(
			function(ServerRequestInterface $request): ResponseInterface {
				return new Response(
					statusCode: ResponseStatus::OK,
					body: \json_encode([
						"id" => $request->getParsedBody()["id"],
						"name" => $request->getParsedBody()["name"]
					]),
					headers: ["Content-Type" => "application/json"]
				);
			}
		);

		$middleware = new JsonMiddleware;
		$response = $middleware->process($request, $handler);

		$parsed_response = \json_decode($response->getBody());

		$this->assertEquals(
			"d5adf9d7-5e55-448e-b1fd-b131c31d7ec1",
			$parsed_response->id
		);

		$this->assertEquals(
			"Limber",
			$parsed_response->name
		);
	}
}