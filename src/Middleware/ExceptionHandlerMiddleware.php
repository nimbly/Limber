<?php

namespace Limber\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class ExceptionHandlerMiddleware implements MiddlewareInterface
{
	/**
	 * The exception handler.
	 *
	 * @var callable
	 */
	protected $exceptionHandler;

	/**
	 * ExceptionHandlerMiddleware constructor.
	 *
	 * @param callable $exceptionHandler
	 */
	public function __construct(callable $exceptionHandler)
	{
		$this->exceptionHandler = $exceptionHandler;
	}

	/**
	 * If this middleware is the first to be processed in the chain, it will catch any exceptions thrown
	 * and pass them off to a default exception handler so that a ResponseInterface can still be returned.
	 *
	 * @param ServerRequestInterface $request
	 * @param RequestHandlerInterface $handler
	 * @return ResponseInterface
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		try {

			$response = $handler->handle($request);

		}
		catch( Throwable $exception ){

			return \call_user_func($this->exceptionHandler, $exception);
		}

		return $response;
	}
}