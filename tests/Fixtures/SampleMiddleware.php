<?php

namespace Nimbly\Limber\Tests\Fixtures;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SampleMiddleware implements MiddlewareInterface
{
	/**
	 * A sample middleware that adds a Request and Response header.
	 *
	 * @param string $param
	 */
	public function __construct(protected string $param = "foo")
	{
	}

	/**
	 * Get parameter.
	 *
	 * @return string
	 */
	public function getParam(): string
	{
		return $this->param;
	}

	/**
	 * @inheritDoc
	 */
	public function process(
		ServerRequestInterface $request,
		RequestHandlerInterface $handler): ResponseInterface
	{
		$request = $request->withAddedHeader("X-Limber-Request", $this->param);
		$response = $handler->handle($request);
		return $response->withAddedHeader("X-Limber-Response", $this->param);
	}
}