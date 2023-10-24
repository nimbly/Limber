<?php

namespace Nimbly\Limber\Middleware;

use Nimbly\Limber\EmptyStream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * This middleware normalizes each response to adhere to HTTP standards for certain edge cases that
 * may cause issues with proxies and clients.
 */
class PrepareHttpResponse implements MiddlewareInterface
{
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$response = $handler->handle($request);

		// Force Content-Length to 0 for 204 No Content responses.
		if( $response->getStatusCode() === 204 ){
			$response = $response->withoutHeader("Content-Length")
			->withoutHeader("Content-Type")
			->withoutHeader("Transfer-Encoding");
		}

		// Set Content-Length header if none provided.
		elseif( $response->hasHeader("Transfer-Encoding") === false &&
				$response->hasHeader("Content-Length") === false &&
				$response->getBody()->getSize() !== null ){
			$response = $response->withHeader("Content-Length", (string) $response->getBody()->getSize());
		}

		// Remove Content-Length header if Transfer-Encoding header is present.
		if( $response->hasHeader("Transfer-Encoding") &&
			$response->hasHeader("Content-Length") ){
			$response = $response->withoutHeader("Content-Length");
		}

		// Set empty body stream for HEAD requests and 204 No Content responses.
		if( \strtoupper($request->getMethod()) === "HEAD" ||
			$response->getStatusCode() === 204 ){
			$response = $response->withBody(new EmptyStream);
		}

		return $response;
	}
}