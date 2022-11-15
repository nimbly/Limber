<?php

namespace Nimbly\Limber;

use Nimbly\Limber\Exceptions\ApplicationException;
use Nimbly\Limber\Middleware\RequestHandler;
use Nimbly\Resolve\Resolve;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use UnexpectedValueException;

class MiddlewareManager
{
	use Resolve;

	public function __construct(
		protected ?ContainerInterface $container = null,
		protected ?ExceptionHandlerInterface $exceptionHandler = null
	)
	{
	}

	/**
	 * Build a RequestHandler chain out of middleware using provided Kernel as the final RequestHandler.
	 *
	 * @param array<MiddlewareInterface> $middleware
	 * @param RequestHandlerInterface|callable $kernel
	 * @return RequestHandlerInterface
	 */
	public function compileMiddleware(array $middleware, RequestHandlerInterface|callable $kernel): RequestHandlerInterface
	{
		return \array_reduce(
			\array_reverse($middleware),
			function(RequestHandlerInterface $nextHandler, MiddlewareInterface $middleware): RequestHandler {
				return new RequestHandler(
					function(ServerRequestInterface $request) use ($nextHandler, $middleware): ResponseInterface {
						return $middleware->process($request, $nextHandler);
					}
				);
			},
			$this->makeRequestHandlerKernel($kernel)
		);
	}

	/**
	 * Make a RequestHandler Kernel.
	 *
	 * @param RequestHandlerInterface|callable $kernel
	 * @return RequestHandlerInterface
	 */
	private function makeRequestHandlerKernel(RequestHandlerInterface|callable $kernel): RequestHandlerInterface
	{
		if( $kernel instanceof RequestHandlerInterface ){
			return $kernel;
		}

		if( !\is_callable($kernel) ){
			throw new UnexpectedValueException("The kernel must be callable or an instance of Psr\Http\Server\RequestHandlerInterface.");
		}

		return new RequestHandler(
			function(ServerRequestInterface $request) use ($kernel): ResponseInterface {
				return \call_user_func($kernel, $request);
			}
		);
	}

	/**
	 * Normalize the given middlewares into instances of MiddlewareInterface.
	 *
	 * @param array<MiddlewareInterface|class-string|array<class-string,array<string,mixed>>> $middlewares
	 * @param ContainerInterface|null $container
	 * @throws ApplicationException
	 * @return array<MiddlewareInterface>
	 */
	private function normalizeMiddleware(array $middlewares, ?ContainerInterface $container = null): array
	{
		$normalized_middlewares = [];

		foreach( $middlewares as $i => $middleware ){

			if( \is_string($i) && \is_array($middleware) ){
				$middleware = $this->make($i, $container, $middleware);
			}

			if( \is_string($middleware) ){
				$middleware = $this->make($middleware, $container);
			}

			if( $middleware instanceof MiddlewareInterface === false ){
				throw new ApplicationException("Provided middleware must be an instance of Psr\Http\Server\MiddlewareInterface or a class-string of an implementation of Psr\Http\Server\MiddlewareInterface.");
			}

			$normalized_middlewares[] = $middleware;
		}

		return $normalized_middlewares;
	}

	/**
	 * Build a RequestHandler chain out of middleware using provided Kernel as the final RequestHandler.
	 *
	 * @param array<MiddlewareInterface> $middleware
	 * @param RequestHandlerInterface $kernel
	 * @return RequestHandlerInterface
	 */
	private function buildHandlerChain(array $middleware, RequestHandlerInterface $kernel): RequestHandlerInterface
	{
		return \array_reduce(
			\array_reverse($middleware),
			function(RequestHandlerInterface $handler, MiddlewareInterface $middleware): RequestHandler {
				return new RequestHandler(
					function(ServerRequestInterface $request) use ($handler, $middleware): ResponseInterface {
						try {

							return $middleware->process($request, $handler);
						}
						catch( Throwable $exception ){
							return $this->handleException($exception, $request);
						}
					}
				);
			},
			$kernel
		);
	}

	/**
	 * Handle a thrown exception by either passing it to user provided exception handler
	 * or throwing it if no handler registered with application.
	 *
	 * @param Throwable $exception
	 * @param ServerRequestInterface $request
	 * @throws Throwable
	 * @return ResponseInterface
	 */
	public function handleException(Throwable $exception, ServerRequestInterface $request): ResponseInterface
	{
		if( !$this->exceptionHandler ){
			throw $exception;
		};

		return $this->exceptionHandler->handle($exception, $request);
	}
}