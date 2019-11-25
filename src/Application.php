<?php

namespace Limber;

use Limber\Exceptions\DispatchException;
use Limber\Middleware\PrepareHttpResponseMiddleware;
use Limber\Middleware\RouteResolverMiddleware;
use Limber\Router\Route;
use Limber\Router\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Throwable;

class Application
{
	/**
     * Global middleware.
     *
     * @var array<MiddlewareInterface>|array<callable>|array<string>
     */
	protected $middleware = [];

	/**
	 * Application specific middleware.
	 *
	 * @var array<MiddlewareInterface>|array<callable>|array<string>
	 */
	protected $applicationMiddleware = [];

	/**
	 * MiddlewareManager instance.
	 *
	 * @var MiddlewareManager
	 */
	protected $middlewareManager;

    /**
     * Application constructor.
     *
     * @param Router $router
     */
    public function __construct(Router $router, MiddlewareManager $middlewareManager = null)
    {
		// Create default MiddlewareManager if none provided.
		if( empty($middlewareManager) ){
			$middlewareManager = new MiddlewareManager;
		}

		$this->middlewareManager = $middlewareManager;

		// Build default application level middleware to be applied.
		$this->applicationMiddleware = [
			new RouteResolverMiddleware($router, $this->middlewareManager),
			new PrepareHttpResponseMiddleware
		];
	}

    /**
     * Set the global middleware to run.
     *
     * @param array<MiddlewareInterface|callable|string> $middlewares
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
	 * Add a default middleware exception handler.
	 *
	 * @param callable $exceptionHandler
	 * @return void
	 */
	public function setExceptionHandler(callable $exceptionHandler): void
	{
		$this->middlewareManager->setExceptionHandler($exceptionHandler);
	}

	/**
	 * Return the kernel callable.
	 *
	 * @return callable
	 */
	private function getKernel(): callable
	{
		return function(ServerRequestInterface $request): ResponseInterface {

			/** @var Route|null $route */
			$route = $request->getAttribute(Route::class);

			if( empty($route) ){
				throw new DispatchException("Route attribute not found on ServerRequest instance.");
			}

			return $route->dispatch($request);
		};
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
		// Compile the middleware into a RequestHandler chain.
		$requestHandler = $this->middlewareManager->compile(
			\array_merge(
				$this->applicationMiddleware,
				$this->middleware
			),
			$this->getKernel()
		);

		// Handle the request
		return $requestHandler->handle($request);
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
				\header(
					\sprintf("%s: %s", $header, \implode(',', $values)),
					false
				);
			}
		}

		if( $response->getStatusCode() !== 204 ){
			echo $response->getBody()->getContents();
		}
    }
}