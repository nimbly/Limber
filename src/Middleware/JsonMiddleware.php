<?php

namespace Nimbly\Limber\Middleware;

use Nimbly\Limber\Exceptions\BadRequestHttpException;
use Nimbly\Limber\Exceptions\NotAcceptableHttpException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * This middleware can be applied when all incoming requests are expected to be JSON.
 * It will deserialize the request body and automatically hydrate the parsed body of the
 * ServerRequestInterface instance.
 *
 * If the request body is not JSON or is malformed, a BadRequestHttpException will be thrown.
 *
 * It will check and match the Content-Type header contains "application/json" and, if
 * not, will throw a NotAcceptableHttpException.
 */
class JsonMiddleware implements MiddlewareInterface
{
	/**
	 * @param boolean $deserialize_as_array Deserialize JSON body as an associative array. If not, it will be deserialized as an anonmyous Object (an object instance of \stdClass).
	 */
	public function __construct(
		protected bool $deserialize_as_array = true
	)
	{
	}

	/**
	 * @inheritDoc
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		if( empty($request->getParsedBody()) &&
			\in_array(\strtolower($request->getMethod()), ["post", "put", "patch"]) ){

			$content_type = \trim($request->getHeaderLine("Content-Type"));

			if( \stripos($content_type, "application/json") === false ){
				throw new NotAcceptableHttpException("Content type \"{$content_type}\" is not acceptable.");
			}

			$body_contents = \trim($request->getBody()->getContents());
			$parsed_body = \json_decode($body_contents, $this->deserialize_as_array);

			if( \json_last_error() !== JSON_ERROR_NONE ) {
				throw new BadRequestHttpException("Malformed JSON request body.");
			}

			$request = $request->withParsedBody($parsed_body);
		}

		return $handler->handle($request);
	}
}