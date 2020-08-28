<?php

namespace Limber;

use Limber\Exceptions\ApplicationException;
use Limber\Middleware\CallableMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class MiddlewareManager
{
	/**
	 * Exception handler instance.
	 *
	 * @var ExceptionHandlerInterface|null
	 */
	protected $exceptionHandler;

	/**
	 * MiddlewareManager constructor.
	 *
	 * @param array<MiddlewareInterface|callable|string> $middleware
	 * @param ExceptionHandlerInterface|null $exceptionHandler
	 */
	public function __construct(
		?ExceptionHandlerInterface $exceptionHandler = null)
	{
		$this->exceptionHandler = $exceptionHandler;
	}

	/**
	 * Set the ExceptionHandler instance.
	 *
	 * @param ExceptionHandlerInterface $exceptionHandler
	 * @return void
	 */
	public function setExceptionHandler(ExceptionHandlerInterface $exceptionHandler): void
	{
		$this->exceptionHandler = $exceptionHandler;
	}

	/**
	 * Build a RequestHandler chain out of middleware using provided Kernel as the final RequestHandler.
	 *
	 * @param array<MiddlewareInterface|callable|string> $middleware
	 * @param RequestHandlerInterface $kernel
	 * @return RequestHandlerInterface
	 */
	public function compile(array $middleware, RequestHandlerInterface $kernel): RequestHandlerInterface
	{
		$middleware = \array_reverse(
			$this->normalizeMiddleware($middleware)
		);

		return \array_reduce(
			$middleware,
			function(RequestHandlerInterface $handler, MiddlewareInterface $middleware): RequestHandler {

				return new RequestHandler(function(ServerRequestInterface $request) use ($handler, $middleware): ResponseInterface {

					try {

						return $middleware->process($request, $handler);

					}
					catch( Throwable $exception ){

						return $this->handleException($exception);
					}

				});
			},
			$kernel
		);
	}

	/**
	 * Normalize the given middlewares into instances of MiddlewareInterface.
	 *
	 * @param array<MiddlewareInterface|callable|string> $middlewares
	 * @throws ApplicationException
	 * @return array<MiddlewareInterface>
	 */
	private function normalizeMiddleware(array $middlewares): array
	{
		return \array_map(function($middleware): MiddlewareInterface {

			if( \is_callable($middleware) ){
				$middleware = new CallableMiddleware($middleware);
			}

			if( \is_string($middleware) &&
				\class_exists($middleware) ){
				$middleware = new $middleware;
			}

			if( $middleware instanceof MiddlewareInterface === false ){
				throw new ApplicationException("Provided middleware must be a string, a \callable, or an instance of Psr\Http\Server\MiddlewareInterface.");
			}

			return $middleware;

		}, $middlewares);
	}

	/**
	 * Handle a thrown exception by either passing it to user provided exception handler
	 * or throwing it if no exception handler is registered.
	 *
	 * @param Throwable $exception
	 * @throws Throwable
	 * @return ResponseInterface
	 */
	private function handleException(Throwable $exception): ResponseInterface
	{
		if( $this->exceptionHandler ){
			return $this->exceptionHandler->handle($exception);
		};

		throw $exception;
	}
}