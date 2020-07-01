<?php

namespace Limber;

use Limber\Middleware\PrepareHttpResponse;
use Limber\Middleware\RouteResolver;
use Limber\Router\Router;
use Psr\Container\ContainerInterface;
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
	 * MiddlewareManager instance.
	 *
	 * @var MiddlewareManager
	 */
	protected $middlewareManager;

	/**
	 * ContainerInterface instance.
	 *
	 * @var ContainerInterface|null
	 */
	protected $container;

	/**
	 * Global middleware.
	 *
	 * @var array
	 */
	protected $middleware = [];

	/**
	 * Request handler chain.
	 *
	 * @var RequestHandlerInterface|null
	 */
	protected $requestHandler;

	/**
	 * Application constructor.
	 *
	 * @param MiddlewareManager
	 */
	public function __construct(
		Router $router,
		MiddlewareManager $middlewareManager)
	{
		$this->router = $router;
		$this->middlewareManager = $middlewareManager;
	}

	/**
	 * Set a ContainerInterface instance to be used when autowiring route handlers.
	 *
	 * @param ContainerInterface $container
	 * @return void
	 */
	public function setContainer(ContainerInterface $container): void
	{
		$this->container = $container;
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
	 * Dispatch a request.
	 *
	 * @param ServerRequestInterface $request
	 * @throws Throwable
	 * @return ResponseInterface
	 */
	public function dispatch(ServerRequestInterface $request): ResponseInterface
	{
		if( empty($this->requestHandler) ){
			$this->requestHandler = $this->middlewareManager->compile(
				\array_merge(
					$this->middleware, // Global user-space middleware
					// Application specific middleware
					[
						new RouteResolver($this->router, $this->middlewareManager),
						new PrepareHttpResponse
					]
				),
				new Kernel($this->container)
			);
		}

		return $this->requestHandler->handle($request);
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