<?php

namespace Limber;

use Limber\Exceptions\ApplicationException;
use Limber\Exceptions\MethodNotAllowedHttpException;
use Limber\Exceptions\NotFoundHttpException;
use Limber\Middleware\CallableMiddleware;
use Limber\Middleware\PrepareHttpResponse;
use Limber\Middleware\RequestHandler;
use Limber\Router\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class Application
{
    /**
     * Router instance.
     *
     * @var Router
     */
    protected $router;

    /**
     * Global middleware.
     *
     * @var array
     */
	protected $middleware = [];

	/**
	 * Registered exception handler.
	 *
	 * @var ?callable
	 */
	protected $exceptionHandler;

    /**
     * Application constructor.
     *
     * @param Router $router
     */
    public function __construct(Router $router)
    {
		$this->router = $router;
    }

    /**
     * Set the global middleware to run.
     *
     * @param array<MiddlewareInterface|callable> $middlewares
     * @return void
     */
    public function setMiddleware(array $middlewares): void
    {
		$this->middleware = $middlewares;
	}

    /**
     * Add a middleware to the stack.
     *
     * @param MiddlewareInterface|callable|string $middleware
     */
    public function addMiddleware($middleware): void
    {
        $this->middleware[] = $middleware;
	}

	/**
	 * Add a default application-level exception handler.
	 *
	 * @param callable $exceptionHandler
	 * @return void
	 */
	public function setExceptionHandler(callable $exceptionHandler): void
	{
		$this->exceptionHandler = $exceptionHandler;
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

		// Normalize the middlewares to be array<MiddlewareInterface>
		$middleware = $this->normalizeMiddleware(
			\array_merge(
				$this->middleware, // Global user-space middleware
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

						// 405 Method Not Allowed
						if( ($methods = $this->router->getMethods($request)) ){
							throw new MethodNotAllowedHttpException($methods);
						}

						// 404 Not Found
						throw new NotFoundHttpException("Route not found");
					}

					return \call_user_func_array(
						$route->getCallableAction(),
						\array_merge(
							[$request],
							\array_values(
								$route->getPathParams($request->getUri()->getPath())
							)
						)
					);

				} catch( Throwable $exception ){

					return $this->handleException($exception);
				}

			})
		);

		return $requestHandler->handle($request);
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
	 * Build a RequestHandler chain out of middleware using provided Kernel as the final RequestHandler.
	 *
	 * @param array<MiddlewareInterface> $middleware
	 * @param RequestHandlerInterface $kernel
	 * @return RequestHandlerInterface
	 */
	private function buildHandlerChain(array $middleware, RequestHandlerInterface $kernel): RequestHandlerInterface
	{
		$middleware = \array_reverse($middleware);

		return \array_reduce($middleware, function(RequestHandlerInterface $handler, MiddlewareInterface $middleware): RequestHandler {

			return new RequestHandler(function(ServerRequestInterface $request) use ($handler, $middleware): ResponseInterface {

				try {

					return $middleware->process($request, $handler);

				}
				catch( Throwable $exception ){

					return $this->handleException($exception);
				}

			});

		}, $kernel);
	}

	/**
	 * Handle a thrown exception by either passing it to user provided exception handler
	 * or throwing it if no handler registered with application.
	 *
	 * @param Throwable $exception
	 * @throws Throwable
	 * @return ResponseInterface
	 */
	private function handleException(Throwable $exception): ResponseInterface
	{
		if( $this->exceptionHandler ){
			return \call_user_func($this->exceptionHandler, $exception);
		};

		throw $exception;
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
                \sprintf("HTTP/%s %s %s", $response->getProtocolVersion(), $response->getStatusCode(), $response->getReasonPhrase())
            );

            foreach( $response->getHeaders() as $header => $values ){
                foreach( $values as $value ){
                    \header(
						\sprintf("%s: %s", $header, $value),
						false
                    );
                }
			}
        }

		if( $response->getStatusCode() !== 204 ){
			echo $response->getBody()->getContents();
		}
    }
}