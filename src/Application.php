<?php

namespace Nimbly\Limber;

use Nimbly\Limber\Exceptions\ApplicationException;
use Nimbly\Limber\Exceptions\MethodNotAllowedHttpException;
use Nimbly\Limber\Exceptions\NotFoundHttpException;
use Nimbly\Limber\Middleware\PrepareHttpResponse;
use Nimbly\Limber\Middleware\RequestHandler;
use Nimbly\Limber\Router\Router;
use Nimbly\Resolve\Resolve;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class Application
{
	use Resolve;
	use MiddlewareManager;

	private RequestHandlerInterface $requestHandler;

	/**
	 * @param Router $router
	 * @param array<MiddlewareInterface|class-string> $middleware
	 * @param ContainerInterface|null $container
	 * @param ExceptionHandlerInterface|null $exceptionHandler
	 */
	public function __construct(
		protected Router $router,
		array $middleware = [],
		protected ?ContainerInterface $container = null,
		protected ?ExceptionHandlerInterface $exceptionHandler = null)
	{
		$this->requestHandler = $this->buildHandlerChain(
			$this->normalizeMiddleware(
				\array_merge(
					$middleware, // Global middleware
					[PrepareHttpResponse::class] // Application specific middleware
				)
			),
			new RequestHandler(function(ServerRequestInterface $request) use ($route): ResponseInterface {

				try {

					if( empty($route) ){

						$methods = $this->router->getSupportedMethods($request);

						// 404 Not Found
						if( empty($methods) ){
							throw new NotFoundHttpException("Route not found");
						}

						// 405 Method Not Allowed
						throw new MethodNotAllowedHttpException($methods);
					}

					$routeHandler = $this->makeCallable(
						thing: $route->getHandler(),
						container: $this->container,
						parameters: \array_merge(
							[ServerRequestInterface::class => $request],
							$route->getPathParameters($request->getUri()->getPath()),
							$request->getAttributes(),
						)
					);

					return $this->call(
						callable: $routeHandler,
						container: $this->container,
						parameters: \array_merge(
							[ServerRequestInterface::class => $request],
							$route->getPathParameters($request->getUri()->getPath()),
							$request->getAttributes(),
						)
					);

				} catch( Throwable $exception ){

					return $this->handleException($exception, $request);
				}
			})
		);
	}

	/**
	 * Dispatch a request.
	 *
	 * @param ServerRequestInterface $request
	 * @throws Throwable
	 * @return ResponseInterface
	 */
	public function dispatch(ServerRequestInterface $request): ResponseInterface
	{
		// Resolve the route now to check for Routed middleware.
		$route = $this->router->resolve($request);

		// Attach route attributes to Request
		$request = $this->attachRequestAttributes(
			$request,
			$route ? $route->getAttributes() : []
		);

		// Normalize the middlewares to be array<MiddlewareInterface>
		$middleware = $this->normalizeMiddleware(
			\array_merge(
				$this->middleware, // Global middleware
				$route ? $route->getMiddleware() : [], // Route specific middleware
				[PrepareHttpResponse::class] // Application specific middleware
			)
		);

		// Build the request handler chain
		$requestHandler = $this->buildHandlerChain(
			$middleware,
			new RequestHandler(function(ServerRequestInterface $request) use ($route): ResponseInterface {

				try {

					if( empty($route) ){

						$methods = $this->router->getSupportedMethods($request);

						// 404 Not Found
						if( empty($methods) ){
							throw new NotFoundHttpException("Route not found");
						}

						// 405 Method Not Allowed
						throw new MethodNotAllowedHttpException($methods);
					}

					$routeHandler = $this->makeCallable(
						thing: $route->getHandler(),
						container: $this->container,
						parameters: \array_merge(
							[ServerRequestInterface::class => $request],
							$route->getPathParameters($request->getUri()->getPath()),
							$request->getAttributes(),
						)
					);

					return $this->call(
						callable: $routeHandler,
						container: $this->container,
						parameters: \array_merge(
							[ServerRequestInterface::class => $request],
							$route->getPathParameters($request->getUri()->getPath()),
							$request->getAttributes(),
						)
					);

				} catch( Throwable $exception ){

					return $this->handleException($exception, $request);
				}
			})
		);

		return $requestHandler->handle($request);
	}

	/**
	 * Attach attributes to the request.
	 *
	 * @param ServerRequestInterface $request
	 * @param array<string,mixed> $attributes
	 * @return ServerRequestInterface
	 */
	private function attachRequestAttributes(ServerRequestInterface $request, array $attributes): ServerRequestInterface
	{
		foreach( $attributes as $attribute => $value ){
			$request = $request->withAttribute($attribute, $value);
		}

		return $request;
	}

	/**
	 * Normalize the given middlewares into instances of MiddlewareInterface.
	 *
	 * @param array<MiddlewareInterface|string|array<class-string,array<string,mixed>>> $middlewares
	 * @throws ApplicationException
	 * @return array<MiddlewareInterface>
	 */
	private function normalizeMiddleware(array $middlewares): array
	{
		$normalized_middlewares = [];

		foreach( $middlewares as $index => $middleware ){

			if( \is_string($middleware) ){
				$middleware = $this->make($middleware);
			}

			elseif( \is_string($index) && \is_array($middleware) ){
				$middleware = $this->make($index, $this->container, $middleware);
			}

			if( empty($middleware) || $middleware instanceof MiddlewareInterface === false ){
				throw new ApplicationException("Provided middleware must be a class-string, a \callable, or an instance of Psr\Http\Server\MiddlewareInterface.");
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
	private function handleException(Throwable $exception, ServerRequestInterface $request): ResponseInterface
	{
		if( !$this->exceptionHandler ){
			throw $exception;
		};

		return $this->exceptionHandler->handle($exception, $request);
	}

	/**
	 * Send a response back to calling client.
	 *
	 * @param ResponseInterface $response
	 * @return void
	 */
	public function send(ResponseInterface $response): void
	{
		if( !\headers_sent() ){
			\header(
				\sprintf(
					"HTTP/%s %s %s",
					$response->getProtocolVersion(),
					$response->getStatusCode(),
					$response->getReasonPhrase()
				)
			);

			foreach( $response->getHeaders() as $header => $values ){
				\header(
					\sprintf("%s: %s", $header, \implode(",", $values)),
					false
				);
			}
		}

		if( $response->getStatusCode() !== 204 ){
			echo $response->getBody()->getContents();
		}
	}
}