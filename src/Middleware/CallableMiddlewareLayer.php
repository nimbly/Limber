<?php

namespace Limber\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CallableMiddlewareLayer implements MiddlewareLayerInterface
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
	public function handle(ServerRequestInterface $request, callable $next): ResponseInterface
	{
		return \call_user_func_array($this->callback, [$request, $next]);
	}
}