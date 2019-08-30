<?php

namespace Limber\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CallableMiddleware implements MiddlewareInterface
{
	/**
	 * Callback instance
	 *
	 * @var callable
	 */
	private $callback;

	/**
	 * CallableMiddlewarLayer constructor
	 *
	 * @param callable $callback
	 */
	public function __construct(callable $callback)
	{
		$this->callback = $callback;
	}

	/**
	 * @inheritDoc
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		return \call_user_func_array($this->callback, [$request, $handler]);
	}
}