<?php

namespace Limber;

use Psr\Http\Message\ResponseInterface;
use Throwable;

interface ExceptionHandlerInterface
{
	/**
	 * Handle an exception being thrown in middleware and return appropriate ResponseInterface instance.
	 *
	 * @param Throwable $exception
	 * @return ResponseInterface
	 */
	public function handle(Throwable $exception): ResponseInterface;
}