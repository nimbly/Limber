<?php

namespace Limber;

use Limber\Middleware\PrepareHttpResponse;
use Limber\Middleware\RouteResolver;
use Limber\Router\Route;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class Application
{
	/**
	 * RouteManager instance.
	 *
	 * @var RouteManager
	 */
	protected $routeManager;

	/**
	 * MiddlewareManager instance.
	 *
	 * @var MiddlewareManager
	 */
	protected $middlewareManager;

	/**
	 * DepedencyManager instance.
	 *
	 * @var DependencyManager
	 */
	protected $dependencyManager;

	/**
	 * Global middleware.
	 *
	 * @var array<string|callable|MiddlewareInterface>
	 */
	protected $middleware = [];

	/**
	 * Compiled request handler chain.
	 *
	 * @var RequestHandlerInterface|null
	 */
	protected $requestHandler;

	/**
	 * Application constructor.
	 *
	 * @param RouteManager $routeManager
	 * @param MiddlewareManager $middlewareManager
	 * @param DependencyManager $dependencyManager
	 */
	public function __construct(
		RouteManager $routeManager,
		MiddlewareManager $middlewareManager,
		DependencyManager $dependencyManager)
	{
		$this->routeManager = $routeManager;
		$this->middlewareManager = $middlewareManager;
		$this->dependencyManager = $dependencyManager;
	}

	/**
	 * Make an Application instance.
	 *
	 * @param array<MiddlewareInterface|callable|string> $middleware
	 * @param ExceptionHandlerInterface|null $exceptionHandler
	 * @param ContainerInterface|null $container
	 * @return self
	 */
	public static function make(
		array $middleware = [],
		?ExceptionHandlerInterface $exceptionHandler = null,
		?ContainerInterface $container = null): self
	{
		return new self(
			new RouteManager,
			new MiddlewareManager($middleware, $exceptionHandler),
			new DependencyManager($container)
		);
	}

	/**
	 * Set the ContainerInterface to use for dependency resolution.
	 *
	 * @param ContainerInterface $container
	 * @return void
	 */
	public function setContainer(ContainerInterface $container): void
	{
		$this->dependencyManager->setContainer($container);
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
	 * Add a global middleware to the stack.
	 *
	 * @param MiddlewareInterface|callable|string $middleware
	 */
	public function addMiddleware($middleware): void
	{
		$this->middleware[] = $middleware;
	}

	/**
	 * Set the default middleware exception handler.
	 *
	 * @param ExceptionHandlerInterface $exceptionHandler
	 * @return void
	 */
	public function setExceptionHandler(ExceptionHandlerInterface $exceptionHandler): void
	{
		$this->middlewareManager->setExceptionHandler($exceptionHandler);
	}

	/**
	 * Add a route.
	 *
	 * @param array<string> $methods
	 * @param string $path
	 * @param callable|string $handler
	 * @return Route
	 */
	public function add(array $methods, string $path, $handler): Route
	{
		return $this->routeManager->add($methods, $path, $handler);
	}

	/**
	 * Add a GET route.
	 *
	 * @param string $path
	 * @param callable|string $handler
	 * @return Route
	 */
	public function get(string $path, $handler): Route
	{
		return $this->routeManager->get($path, $handler);
	}

	/**
	 * Add a POST route.
	 *
	 * @param string $path
	 * @param callable|string $handler
	 * @return Route
	 */
	public function post(string $path, $handler): Route
	{
		return $this->routeManager->post($path, $handler);
	}

	/**
	 * Add a PUT route.
	 *
	 * @param string $path
	 * @param callable|string $handler
	 * @return Route
	 */
	public function put(string $path, $handler): Route
	{
		return $this->routeManager->put($path, $handler);
	}

	/**
	 * Add a PATCH route.
	 *
	 * @param string $path
	 * @param callable|string $handler
	 * @return Route
	 */
	public function patch(string $path, $handler): Route
	{
		return $this->routeManager->patch($path, $handler);
	}

	/**
	 * Add a DELETE route.
	 *
	 * @param string $path
	 * @param callable|string $handler
	 * @return Route
	 */
	public function delete(string $path, $handler): Route
	{
		return $this->routeManager->delete($path, $handler);
	}

	/**
	 * Add an OPTIONS route.
	 *
	 * @param string $path
	 * @param callable|string $handler
	 * @return Route
	 */
	public function options(string $path, $handler): Route
	{
		return $this->routeManager->options($path, $handler);
	}

	/**
	 * Create a route grouping.
	 *
	 * @param array<string,mixed> $config
	 * @param callable $callback
	 * @return void
	 */
	public function group(array $config, callable $callback): void
	{
		$this->routeManager->group($config, $callback);
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
					[
						new RouteResolver($this->routeManager, $this->middlewareManager),
						new PrepareHttpResponse
					],
					$this->middleware
				),
				new Kernel($this->dependencyManager)
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