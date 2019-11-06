<?php

namespace Limber;

use Limber\Exceptions\ApplicationException;
use Limber\Middleware\CallableMiddleware;
use Limber\Middleware\ExceptionHandlerMiddleware;
use Limber\Middleware\PrepareHttpResponseMiddleware;
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
     * @var array<MiddlewareInterface>|array<callable>|array<string>
     */
	protected $middleware = [];

	/**
	 * Application-level middleware.
	 *
	 * @var array<MiddlewareInterface>|array<callable>|array<string>
	 */
	protected $applicationMiddleware = [];

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
	 * Enables an internal middleware to automatically prepare and normalize your
	 * responses to better adhere to official HTTP specifications.
	 *
	 * @return void
	 */
	public function enablePrepareResponse(): void
	{
		$this->applicationMiddleware[] = new PrepareHttpResponseMiddleware;
	}

	/**
	 * Add a default middleware exception handler.
	 *
	 * @param callable $exceptionHandler
	 * @return void
	 */
	public function setExceptionHandler(callable $exceptionHandler): void
	{
		$this->applicationMiddleware[] = new ExceptionHandlerMiddleware($exceptionHandler);
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
		// Resolve the route now to check for Route middleware.
		$route = $this->router->resolve($request);

		// Compile the middleware into a RequestHandler chain.
		$requestHandler = $this->compileMiddleware(
			\array_merge(
				$route ? $route->getMiddleware() : [], // Apply Route level middleware last
				$this->middleware, // Apply global middleware
				$this->applicationMiddleware // Apply application level middleware first
			),
			new Kernel($this->router, $route)
		);

		// Handle the request
		return $requestHandler->handle($request);
	}

	/**
	 * Compile middleware into a RequestHandlerInterface chain.
	 *
	 * @param array<MiddlewareInterface|string|callable> $middleware
	 * @param RequestHandlerInterface $kernel
	 * @return RequestHandlerInterface
	 */
	private function compileMiddleware(array $middleware, RequestHandlerInterface $kernel): RequestHandlerInterface
	{
		return $this->buildHandlerChain(
			$this->normalizeMiddleware($middleware),
			$kernel
		);
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

				return $middleware->process($request, $handler);

			});

		}, $kernel);
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