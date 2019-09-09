<?php

namespace Limber\Tests;

use Capsule\Response;
use Capsule\ResponseStatus;
use Capsule\ServerRequest;
use Limber\EmptyStream;
use Limber\Middleware\PrepareHttpResponse;
use Limber\Middleware\RequestHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @covers Limber\Middleware\PrepareHttpResponse
 * @covers Limber\Middleware\RequestHandler
 */
class PrepareHttpResponseTest extends TestCase
{
	public function test_204_no_content_responses_removes_content_based_headers()
	{
		$prepareHttpResponseMiddleware = new PrepareHttpResponse;

		$response = $prepareHttpResponseMiddleware->process(
			ServerRequest::create('get', 'http://example.org/foo', null, [], [], [], []),
			new RequestHandler(function(ServerRequestInterface $request): ResponseInterface {

				return new Response(
					ResponseStatus::NO_CONTENT,
					null,
					[
						'Content-Type' => 'application/json',
						'Content-Length' => 100,
						'Transfer-Encoding' => 'foo'
					]
				);
			})
		);

		$this->assertFalse($response->hasHeader('Content-Type'));
		$this->assertFalse($response->hasHeader('Content-Length'));
		$this->assertFalse($response->hasHeader('Transfer-Encoding'));
	}

	public function test_204_no_content_responses_replaces_body_with_empty_stream()
	{
		$prepareHttpResponseMiddleware = new PrepareHttpResponse;

		$response = $prepareHttpResponseMiddleware->process(
			ServerRequest::create('get', 'http://example.org/foo', null, [], [], [], []),
			new RequestHandler(function(ServerRequestInterface $request): ResponseInterface {

				return new Response(
					ResponseStatus::NO_CONTENT,
					null,
					[
						'Content-Type' => 'application/json',
						'Content-Length' => 100,
						'Transfer-Encoding' => 'foo'
					]
				);
			})
		);

		$this->assertInstanceOf(
			EmptyStream::class,
			$response->getBody()
		);
	}

	public function test_setting_content_length_header_if_none_provided()
	{
		$prepareHttpResponseMiddleware = new PrepareHttpResponse;

		$response = $prepareHttpResponseMiddleware->process(
			ServerRequest::create('get', 'http://example.org/foo', null, [], [], [], []),
			new RequestHandler(function(ServerRequestInterface $request): ResponseInterface {

				return new Response(
					ResponseStatus::OK,
					"Ok"
				);
			})
		);

		$this->assertTrue($response->hasHeader('Content-Length'));
		$this->assertEquals(2, $response->getHeader('Content-Length')[0]);
	}

	public function test_removing_content_length_header_if_transfer_encoding_header_present()
	{
		$prepareHttpResponseMiddleware = new PrepareHttpResponse;

		$response = $prepareHttpResponseMiddleware->process(
			ServerRequest::create('get', 'http://example.org/foo', null, [], [], [], []),
			new RequestHandler(function(ServerRequestInterface $request): ResponseInterface {

				return new Response(
					ResponseStatus::OK,
					"Ok",
					[
						'Transfer-Encoding' => 'Foo',
						'Content-Length' => 100
					]
				);
			})
		);

		$this->assertFalse($response->hasHeader('Content-Length'));
	}

	public function test_head_methods_return_an_empty_stream()
	{
		$prepareHttpResponseMiddleware = new PrepareHttpResponse;

		$response = $prepareHttpResponseMiddleware->process(
			ServerRequest::create('head', 'http://example.org/foo', null, [], [], [], []),
			new RequestHandler(function(ServerRequestInterface $request): ResponseInterface {

				return new Response(
					ResponseStatus::OK,
					"Ok",
					[
						'Transfer-Encoding' => 'Foo',
						'Content-Length' => 100
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