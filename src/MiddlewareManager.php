<?php

namespace Limber;

use Limber\Exceptions\ApplicationException;
use Limber\Exceptions\MiddlewareException;
use Limber\Middleware\CallableMiddleware;
use Limber\Middleware\RequestHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareManager
{
	/**
	 * Exception handler.
	 *
	 * @var callable|null
	 */
	protected $exceptionHandler;

	/**
	 * MiddlewareManager constructor.
	 *
	 * @param callable|null $exceptionHandler
	 */
	public function __construct(?callable $exceptionHandler = null)
	{
		$this->exceptionHandler = $exceptionHandler;
	}

	/**
	 * Set the exception handler for the middleware chain.
	 *
	 * @param callable $exceptionHandler
	 * @return void
	 */
	public function setExceptionHandler(callable $exceptionHandler): void
	{
		$this->exceptionHandler = $exceptionHandler;
	}

	/**
	 * Build a RequestHandler chain out of middleware using provided Kernel as the final RequestHandler.
	 *
	 * @param array<MiddlewareInterface>|array<callable>|array<string> $middleware
	 * @param RequestHandlerInterface|callable $kernel
	 * @return RequestHandlerInterface
	 */
	public function compile(array $middleware, $kernel): RequestHandlerInterface
	{
		$middlewares = \array_reverse(
			$this->normalize($middleware)
		);

		return \array_reduce($middlewares, function(RequestHandlerInterface $nextHandler, MiddlewareInterface $middleware): RequestHandler {

			return new RequestHandler(function(ServerRequestInterface $request) use ($nextHandler, $middleware): ResponseInterface {

				return $middleware->process($request, $nextHandler);

			}, $this->exceptionHandler);

		}, $this->makeRequestHandlerKernel($kernel));
	}

	/**
	 * Make a RequestHandler Kernel.
	 *
	 * @param RequestHandlerInterface|callable $kernel
	 * @return RequestHandlerInterface
	 */
	private function makeRequestHandlerKernel($kernel): RequestHandlerInterface
	{
		if( $kernel instanceof RequestHandlerInterface ){
			return $kernel;
		}

		if( !\is_callable($kernel) ){
			throw new MiddlewareException("The kernel must be callable or an instance of Psr\Http\Server\RequestHandlerInterface.");
		}

		return new RequestHandler(function(ServerRequestInterface $request) use ($kernel): ResponseInterface {

			return \call_user_func($kernel, $request);

		}, $this->exceptionHandler);
	}

	/**
	 * Normalize the given middlewares into instances of MiddlewareInterface.
	 *
	 * @param array<MiddlewareInterface|callable|string> $middlewares
	 * @throws ApplicationException
	 * @return array<MiddlewareInterface>
	 */
	private function normalize(array $middlewares): array
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
}