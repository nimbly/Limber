<?php

namespace Nimbly\Limber\Router;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

class Router implements RouterInterface
{
	/**
	 * Route path parameter patterns.
	 *
	 * @var array<string,string>
	 */
	protected static array $patterns = [
		"alpha" => "[a-z]+",
		"int" => "\d+",
		"alphanumeric" => "[a-z0-9]+",
		"uuid" => "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}",
		"hex" => "[a-f0-9]+"
	];

	/**
	 * @var array{scheme:string|null,prefix:string|null,namespace:string|null,hostnames:array<string>,middleware:array<string|MiddlewareInterface>,attributes:array<string,mixed>}
	 */
	protected array $group_config = [
		"scheme" => null,
		"prefix" => null,
		"namespace" => null,
		"hostnames" => [],
		"middleware" => [],
		"attributes" => [],
	];

	/**
	 * Array of routes indexed by HTTP method.
	 *
	 * @var array<string,array<Route>>
	 */
	protected array $routes = [];

	/**
	 * @param array<Route> $routes
	 */
	public function __construct(array $routes = [])
	{
		foreach( $routes as $route ){
			foreach( $route->getMethods() as $method ){
				$this->routes[$method][] = $route;
			}
		}
	}

	/**
	 * Set a path regex pattern.
	 *
	 * @param string $name
	 * @param string $pattern
	 * @return void
	 */
	public static function setPattern(string $name, string $pattern): void
	{
		self::$patterns[$name] = $pattern;
	}

	/**
	 * Get a particular path pattern by name.
	 *
	 * @param string $name
	 * @return string|null
	 */
	public static function getPattern(string $name): ?string
	{
		return self::$patterns[$name] ?? null;
	}

	/**
	 * @inheritDoc
	 */
	public function add(
		array $methods,
		string $path,
		string|callable $handler,
		?string $scheme = null,
		array $hostnames = [],
		array $middleware = [],
		array $attributes = [],
	): Route
	{
		if( $this->group_config["prefix"] ){
			$path = \trim($this->group_config["prefix"], "/") . "/" . \trim($path, "/");
		}

		if( $this->group_config["namespace"] && \is_string($handler) && \strpos($handler, "@") !== false ){
			$handler = \sprintf(
				"\\%s\\%s",
				\trim($this->group_config["namespace"], "\\"),
				\trim($handler, "\\")
			);
		}

		$route = new Route(
			methods: $methods,
			path: $path,
			handler: $handler,
			middleware: \array_merge($this->group_config["middleware"], $middleware),
			scheme: $scheme ?? $this->group_config["scheme"],
			hostnames: $hostnames ?: $this->group_config["hostnames"],
			attributes: $attributes ?: $this->group_config["attributes"]
		);

		foreach( $route->getMethods() as $method ){
			$this->routes[$method][] = $route;
		}

		return $route;
	}

	/**
	 * @inheritDoc
	 */
	public function resolve(ServerRequestInterface $request): ?Route
	{
		/**
		 * @var Route $route
		 */
		foreach( $this->routes[$request->getMethod()] ?? [] as $route ){
			if( $route->matchScheme($request->getUri()->getScheme()) &&
				$route->matchHostname($request->getUri()->getHost()) &&
				$route->matchPath($request->getUri()->getPath()) ){
				return $route;
			}
		}

		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function getSupportedMethods(ServerRequestInterface $request): array
	{
		$methods = [];

		/**
		 * @var Route $route
		 */
		foreach( $this->routes as $method => $routes ){
			foreach($routes as $route ){
				if( $route->matchScheme($request->getUri()->getScheme()) &&
					$route->matchHostname($request->getUri()->getHost()) &&
					$route->matchPath($request->getUri()->getPath()) ){
					$methods[] = $method;
				}
			}
		}

		return $methods;
	}

	/**
	 * Add a GET route.
	 *
	 * @param string $path
	 * @param string|callable $handler
	 * @param string|null $scheme
	 * @param array<string> $hostnames
	 * @param array<string|MiddlewareInterface> $middleware
	 * @param array<string,mixed> $attributes
	 * @return Route
	 */
	public function get(
		string $path,
		string|callable $handler,
		array $middleware = [],
		?string $scheme = null,
		array $hostnames = [],
		array $attributes = []): Route
	{
		return $this->add(
			methods: ["GET", "HEAD"],
			path: $path,
			handler: $handler,
			scheme: $scheme,
			hostnames: $hostnames,
			middleware: $middleware,
			attributes: $attributes
		);
	}

	/**
	 * Add a POST route.
	 *
	 * @param string $path
	 * @param string|callable $handler
	 * @param string|null $scheme
	 * @param array<string> $hostnames
	 * @param array<string|MiddlewareInterface> $middleware
	 * @param array<string,mixed> $attributes
	 * @return Route
	 */
	public function post(
		string $path,
		string|callable $handler,
		?string $scheme = null,
		array $hostnames = [],
		array $middleware = [],
		array $attributes = []): Route
	{
		return $this->add(
			methods: ["POST"],
			path: $path,
			handler: $handler,
			scheme: $scheme,
			hostnames: $hostnames,
			middleware: $middleware,
			attributes: $attributes
		);
	}

	/**
	 * Add a PUT route.
	 *
	 * @param string $path
	 * @param string|callable $handler
	 * @param string|null $scheme
	 * @param array<string> $hostnames
	 * @param array<string|MiddlewareInterface> $middleware
	 * @param array<string,mixed> $attributes
	 * @return Route
	 */
	public function put(
		string $path,
		string|callable $handler,
		?string $scheme = null,
		array $hostnames = [],
		array $middleware = [],
		array $attributes = []): Route
	{
		return $this->add(
			methods: ["PUT"],
			path: $path,
			handler: $handler,
			scheme: $scheme,
			hostnames: $hostnames,
			middleware: $middleware,
			attributes: $attributes
		);
	}

	/**
	 * Add a PATCH route.
	 *
	 * @param string $path
	 * @param string|callable $handler
	 * @param string|null $scheme
	 * @param array<string> $hostnames
	 * @param array<string|MiddlewareInterface> $middleware
	 * @param array<string,mixed> $attributes
	 * @return Route
	 */
	public function patch(
		string $path,
		string|callable $handler,
		?string $scheme = null,
		array $hostnames = [],
		array $middleware = [],
		array $attributes = []): Route
	{
		return $this->add(
			methods: ["PATCH"],
			path: $path,
			handler: $handler,
			scheme: $scheme,
			hostnames: $hostnames,
			middleware: $middleware,
			attributes: $attributes
		);
	}

	/**
	 * Add a DELETE route.
	 *
	 * @param string $path
	 * @param string|callable $handler
	 * @param string|null $scheme
	 * @param array<string> $hostnames
	 * @param array<string|MiddlewareInterface> $middleware
	 * @param array<string,mixed> $attributes
	 * @return Route
	 */
	public function delete(
		string $path,
		string|callable $handler,
		?string $scheme = null,
		array $hostnames = [],
		array $middleware = [],
		array $attributes = []): Route
	{
		return $this->add(
			methods: ["DELETE"],
			path: $path,
			handler: $handler,
			scheme: $scheme,
			hostnames: $hostnames,
			middleware: $middleware,
			attributes: $attributes
		);
	}

	/**
	 * Add a HEAD route.
	 *
	 * @param string $path
	 * @param string|callable $handler
	 * @param string|null $scheme
	 * @param array<string> $hostnames
	 * @param array<string|MiddlewareInterface> $middleware
	 * @param array<string,mixed> $attributes
	 * @return Route
	 */
	public function head(
		string $path,
		string|callable $handler,
		?string $scheme = null,
		array $hostnames = [],
		array $middleware = [],
		array $attributes = []): Route
	{
		return $this->add(
			methods: ["HEAD"],
			path: $path,
			handler: $handler,
			middleware: $middleware,
			scheme: $scheme,
			hostnames: $hostnames,
			attributes: $attributes
		);
	}

	/**
	 * Add an OPTIONS route.
	 *
	 * @param string $path
	 * @param string|callable $handler
	 * @param string|null $scheme
	 * @param array<string> $hostnames
	 * @param array<string|MiddlewareInterface> $middleware
	 * @param array<string,mixed> $attributes
	 * @return Route
	 */
	public function options(
		string $path,
		string|callable $handler,
		?string $scheme = null,
		array $hostnames = [],
		array $middleware = [],
		array $attributes = []): Route
	{
		return $this->add(
			methods: ["OPTIONS"],
			path: $path,
			handler: $handler,
			scheme: $scheme,
			hostnames: $hostnames,
			middleware: $middleware,
			attributes: $attributes
		);
	}

	/**
	 * Group routes together with a set of shared configuration options.
	 *
	 * @param callable $routes
	 * @param string|null $namespace
	 * @param string|null $prefix
	 * @param string|null $scheme
	 * @param array<string|MiddlewareInterface> $middleware
	 * @param array<string> $hostnames
	 * @param array<string,mixed> $attributes
	 * @return void
	 */
	public function group(
		callable $routes,
		?string $namespace = null,
		?string $prefix = null,
		?string $scheme = null,
		array $middleware = [],
		array $hostnames = [],
		array $attributes = []): void
	{
		// Save current config
		$previous_config = $this->group_config;

		// Build the new merged config
		$this->group_config = [
			"scheme"=> $scheme ?? $this->group_config["scheme"] ?? null,
			"hostnames" => $hostnames ?: $this->group_config["hostnames"],
			"prefix" => $prefix ?? $this->group_config["prefix"] ?? null,
			"namespace" => $namespace ?? $this->group_config["namespace"] ?? null,
			"middleware" => \array_merge(
				$this->group_config["middleware"],
				$middleware
			),
			"attributes" => $attributes ?: $this->group_config["attributes"]
		];

		// Process routes in closure
		\call_user_func($routes, $this);

		// Restore previous config
		$this->group_config = $previous_config;
	}
}