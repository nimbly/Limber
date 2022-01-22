<?php

namespace Nimbly\Limber\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestHandler implements RequestHandlerInterface
{
	/**
	 * The callable handler.
	 *
	 * @var callable
	 */
	protected $handler;

	/**
	 * RequestHandler constructor.
	 *
	 * @param callable $handler
	 */
	public function __construct(callable $handler)
	{
		$this->handler = $handler;
	}

	/**
	 * Handle server request.
	 *
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		return \call_user_func($this->handler, $request);
	}
}