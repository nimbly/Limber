<?php

namespace Limber\Tests;

use Nimbly\Capsule\Response;
use Nimbly\Capsule\ResponseStatus;
use Nimbly\Capsule\ServerRequest;
use Nimbly\Capsule\Stream\BufferStream;
use Nimbly\Limber\EmptyStream;
use Nimbly\Limber\Middleware\PrepareHttpResponse;
use Nimbly\Limber\Middleware\RequestHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @covers Nimbly\Limber\Middleware\PrepareHttpResponse
 * @covers Nimbly\Limber\Middleware\RequestHandler
 */
class PrepareHttpResponseTest extends TestCase
{
	public function test_204_no_content_responses_removes_content_based_headers(): void
	{
		$prepareHttpResponseMiddleware = new PrepareHttpResponse;

		$response = $prepareHttpResponseMiddleware->process(
			new ServerRequest("get", "http://example.org/foo"),
			new RequestHandler(function(ServerRequestInterface $request): ResponseInterface {

				return new Response(
					ResponseStatus::NO_CONTENT,
					null,
					[
						"Content-Type" => "application/json",
						"Content-Length" => "100",
						"Transfer-Encoding" => "foo"
					]
				);
			})
		);

		$this->assertFalse($response->hasHeader("Content-Type"));
		$this->assertFalse($response->hasHeader("Content-Length"));
		$this->assertFalse($response->hasHeader("Transfer-Encoding"));
	}

	public function test_204_no_content_response_replaces_body_with_empty_stream(): void
	{
		$prepareHttpResponseMiddleware = new PrepareHttpResponse;

		$response = $prepareHttpResponseMiddleware->process(
			new ServerRequest("get", "http://example.org/foo"),
			new RequestHandler(function(ServerRequestInterface $request): ResponseInterface {

				return new Response(
					ResponseStatus::NO_CONTENT,
					null,
					[
						"Content-Type" => "application/json",
						"Content-Length" => "100",
						"Transfer-Encoding" => "foo"
					]
				);
			})
		);

		$this->assertInstanceOf(
			EmptyStream::class,
			$response->getBody()
		);
	}

	public function test_setting_content_length_header_if_none_provided(): void
	{
		$prepareHttpResponseMiddleware = new PrepareHttpResponse;

		$response = $prepareHttpResponseMiddleware->process(
			new ServerRequest("get", "http://example.org/foo"),
			new RequestHandler(function(ServerRequestInterface $request): ResponseInterface {

				return new Response(
					ResponseStatus::OK,
					"Ok"
				);
			})
		);

		$this->assertTrue($response->hasHeader("Content-Length"));
		$this->assertEquals(2, $response->getHeader("Content-Length")[0]);
	}

	public function test_skip_setting_content_length_header_if_none_provided_and_size_is_null(): void
	{
		$prepareHttpResponseMiddleware = new PrepareHttpResponse;

		$response = $prepareHttpResponseMiddleware->process(
			new ServerRequest("get", "http://example.org/foo"),
			new RequestHandler(function(ServerRequestInterface $request): ResponseInterface {
				return new Response(
					ResponseStatus::OK,
					new class extends BufferStream {
						public function getSize(): ?int
						{
							return null;
						}
					}
				);
			})
		);

		$this->assertFalse($response->hasHeader("Content-Length"));
	}

	public function test_removing_content_length_header_if_transfer_encoding_header_present(): void
	{
		$prepareHttpResponseMiddleware = new PrepareHttpResponse;

		$response = $prepareHttpResponseMiddleware->process(
			new ServerRequest("get", "http://example.org/foo"),
			new RequestHandler(function(ServerRequestInterface $request): ResponseInterface {

				return new Response(
					ResponseStatus::OK,
					"Ok",
					[
						"Transfer-Encoding" => "Foo",
						"Content-Length" => "100"
					]
				);
			})
		);

		$this->assertFalse($response->hasHeader("Content-Length"));
	}

	public function test_head_methods_return_an_empty_stream(): void
	{
		$prepareHttpResponseMiddleware = new PrepareHttpResponse;

		$response = $prepareHttpResponseMiddleware->process(
			new ServerRequest("head", "http://example.org/foo"),
			new RequestHandler(function(ServerRequestInterface $request): ResponseInterface {

				return new Response(
					ResponseStatus::OK,
					"Ok",
					[
						"Transfer-Encoding" => "Foo",
						"Content-Length" => "100"
					]
				);
			})
		);

		$this->assertInstanceOf(
			EmptyStream::class,
			$response->getBody()
		);
	}
}