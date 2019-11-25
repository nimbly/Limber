<?php

namespace Limber;

use Psr\Http\Message\ResponseInterface;
use Throwable;

class ExceptionManager
{
	/**
	 * Exception handler instance.
	 *
	 * @var callable|null
	 */
	protected $handler;

	public function __construct(callable $handler = null)
	{
		$this->handler = $handler;
	}

	/**
	 * Set the exception handler.
	 *
	 * @param callable $handler
	 * @return void
	 */
	public function setHandler(callable $handler): void
	{
		$this->handler = $handler;
	}

	/**
	 * Handle an exception.
	 *
	 * @param Throwable $exception
	 * @throws Throwable
	 * @return ResponseInterface
	 */
	public function handle(Throwable $exception): ResponseInterface
	{
		if( empty($this->handler) ){
			throw $exception;
		}

		return \call_user_func($this->handler, $exception);
	}
}