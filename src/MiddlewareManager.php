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

/**
 * The MiddlewareManager is responsible for normalizing and compiling middlewares into a
 * RequestHandlerInterface instance with dependency injection capabilities if needed.
 */
class MiddlewareManager
{
	use Resolve;

	/**
	 * @param ContainerInterface|null $container
	 * @param ExceptionHandlerInterface|null $exceptionHandler
	 */
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
	 * @param RequestHandlerInterface $kernel
	 * @return RequestHandlerInterface
	 */
	public function compile(array $middleware, RequestHandlerInterface $kernel): RequestHandlerInterface
	{
		if( empty($middleware) ){
			return new RequestHandler(
				function(ServerRequestInterface $request) use ($kernel): ResponseInterface {
					try {

						return $kernel->handle($request);
					}
					catch( Throwable $exception ){
						return $this->handleException($exception, $request);
					}
				}
			);
		}

		return \array_reduce(
			$middleware,
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
	 * Normalize the given middlewares into instances of MiddlewareInterface.
	 *
	 * @param array<MiddlewareInterface|class-string|array<class-string,array<string,mixed>>> $middlewares
	 * @throws ApplicationException
	 * @return array<MiddlewareInterface>
	 */
	public function normalize(array $middlewares): array
	{
		$normalized_middlewares = [];

		foreach( $middlewares as $index => $middleware ){

			if( \is_string($middleware) ){
				$middleware = $this->make($middleware, $this->container);
			}

			elseif( \is_string($index) && \is_array($middleware) ){
				$middleware = $this->make($index, $this->container, $middleware);
			}

			if( empty($middleware) || $middleware instanceof MiddlewareInterface === false ){
				throw new ApplicationException("Provided middleware must be an instance of Psr\Http\Server\MiddlewareInterface or a class-string that references an Psr\Http\Server\MiddlewareInterface implementation.");
			}

			$normalized_middlewares[] = $middleware;
		}

		return $normalized_middlewares;
	}

	/**
	 * Handle a caught exception by passing it to the user supplied ExceptionHandler.
	 * If no custom exception handler was provided, throw the original exception.
	 *
	 * @param Throwable $exception
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 */
	private function handleException(Throwable $exception, ServerRequestInterface $request): ResponseInterface
	{
		if( !$this->exceptionHandler ){
			throw $exception;
		}

		return $this->exceptionHandler->handle($exception, $request);
	}
}