<?php

namespace Nimbly\Limber\Tests\Fixtures;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SampleMiddleware implements MiddlewareInterface
{
	/**
	 * Parameter
	 *
	 * @var string
	 */
	protected $param;

	/**
	 * A sample middleware.
	 *
	 * @param string $param
	 */
	public function __construct(string $param = "foo")
	{
		$this->param = $param;
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
		return $handler->handle($request);
	}
}