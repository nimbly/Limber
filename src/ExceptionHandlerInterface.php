<?php

namespace Nimbly\Limber;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

interface ExceptionHandlerInterface
{
	/**
	 * Handle an exception thrown within middleware.
	 *
	 * @param Throwable $exception
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 */
	public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface;
}