<?php

namespace Limber\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class RequestHandler implements RequestHandlerInterface
{
	/**
	 * The callable handler.
	 *
	 * @var callable
	 */
	protected $handler;

	/**
	 * The exception handler.
	 *
	 * @var callable|null
	 */
	protected $exceptionHandler;

	/**
	 * RequestHandler constructor.
	 *
	 * @param callable $handler
	 * @param callable|null $exceptionHandler
	 */
	public function __construct(callable $handler, ?callable $exceptionHandler = null)
	{
		$this->handler = $handler;
		$this->exceptionHandler = $exceptionHandler;
	}

	/**
	 * Handle server request.
	 *
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		try {

			$response = \call_user_func($this->handler, $request);

		}
		catch( Throwable $exception ){

			if( empty($this->exceptionHandler) ){
				throw $exception;
			}

			$response = \call_user_func($this->exceptionHandler, $exception);
		}

		return $response;
	}
}