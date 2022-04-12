<?php

namespace Nimbly\Limber\Router;

use Nimbly\Limber\Exceptions\RouteException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

class Router implements RouterInterface
{
	/**
	 * @var array<string,mixed>
	 */
	protected array $config = [
		"scheme" => null,
		"host" => null,
		"prefix" => null,
		"namespace" => null,
		"hostnames" => [],
		"middleware" => [],
		"attributes" => [],
	];

	/**
	 * Route path parameter patterns.
	 *
	 * @var array<string,string>
	 */
	protected array $patterns = [
		"alpha" => "[a-z]+",
		"int" => "\d+",
		"alphanumeric" => "[a-z0-9]+",
		"uuid" => "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}",
		"hex" => "[a-f0-9]+"
	];

	/**
	 * Array of routes indexed by HTTP method.
	 *
	 * @var array<string,array<Route>>
	 */
	protected array $routes = [];

	/**
	 * @param array<Route> $routes
	 * @param array<string,string> $patterns
	 */
	public function __construct(
		array $routes = [],
		array $patterns = [])
	{
		foreach( $routes as $route ){
			foreach( $route->getMethods() as $method ){
				$this->routes[$method][] = $route;
			}
		}

		$this->patterns = \array_merge(
			$this->patterns,
			$patterns
		);
	}

	/**
	 * Get all routes.
	 *
	 * @return array<Route>
	 */
	public function getRoutes(): array
	{
		$routes = [];
		foreach( $this->routes as $indexedRoutes ){
			$routes = \array_merge($routes, $indexedRoutes);
		}

		return $routes;
	}

	/**
	 * @inheritDoc
	 */
	public function add(
		array $methods,
		string $path,
		string|callable $handler,
		array $middleware = [],
		?string $scheme = null,
		array $hostnames = [],
		array $attributes = [],
	): Route
	{
		if( $this->config["prefix"] ){
			$path = \trim($this->config["prefix"], "/") . "/" . \trim($path, "/");
		}

		if( $this->config["namespace"] && \is_string($handler) && \strpos($handler, "@") ){
			$handler = \sprintf(
				"\\%s\\%s",
				\trim($this->config["namespace"], "\\"),
				\trim($handler, "\\")
			);
		}

		$route = new Route(
			methods: $methods,
			path: $this->compileRegexPattern($path),
			handler: $handler,
			middleware: \array_merge($this->config["middleware"], $middleware),
			scheme: $scheme ?? $this->config["scheme"],
			hostnames: $hostnames ?: $this->config["hostnames"],
			attributes: $attributes ?: $this->config["attributes"]
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
	 * Compile the regular expression for path matching.
	 *
	 * @param array<string,string> $patterns
	 * @return string
	 */
	private function compileRegexPattern(string $path): string
	{
		$parts = [];

		foreach( \explode("/", \trim($path, "/")) as $part ){

			// Is this a named parameter?
			if( \preg_match("/{([a-z0-9_]+)(?:\:([a-z0-9_]+))?}/i", $part, $match) ){

				// Predefined pattern
				if( isset($match[2]) ){
					$part = $this->getPattern($match[2]);

					if( empty($part) ){
						throw new RouteException("Router pattern \"{$match[2]}\" not found.");
					}
				}

				// Match anything
				else {
					$part = "[^\/]+";
				}

				$part = "(?<{$match[1]}>{$part})";
			}

			$parts[] = $part;
		}

		return \sprintf("/^%s$/", \implode("\/", $parts));
	}

	/**
	 * Set a route pattern.
	 *
	 * @param string $name
	 * @param string $pattern
	 * @return void
	 */
	public function setPattern(string $name, string $pattern): void
	{
		$this->patterns[$name] = $pattern;
	}

	/**
	 * Get a particular pattern by name.
	 *
	 * @param string $name
	 * @return string|null
	 */
	public function getPattern(string $name): ?string
	{
		return $this->patterns[$name] ?? null;
	}

	/**
	 * Get all patterns.
	 *
	 * @return array<string,string>
	 */
	public function getPatterns(): array
	{
		return $this->patterns;
	}

	/**
	 * Add a GET route.
	 *
	 * @param string $path
	 * @param string|callable $handler
	 * @param array<string|MiddlewareInterface> $middleware
	 * @param string|null $namespace
	 * @param string|null $scheme
	 * @param array<string> $hostnames
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
		return $this->add(["GET", "HEAD"], $path, $handler, $middleware, $scheme, $hostnames, $attributes);
	}

	/**
	 * Add a POST route.
	 *
	 * @param string $path
	 * @param string|callable $handler
	 * @param array<string|MiddlewareInterface> $middleware
	 * @param string|null $namespace
	 * @param string|null $scheme
	 * @param array<string> $hostnames
	 * @param array<string,mixed> $attributes
	 * @return Route
	 */
	public function post(
		string $path,
		string|callable $handler,
		array $middleware = [],
		?string $scheme = null,
		array $hostnames = [],
		array $attributes = []): Route
	{
		return $this->add(["POST"], $path, $handler, $middleware, $scheme, $hostnames, $attributes);
	}

	/**
	 * Add a PUT route.
	 *
	 * @param string $path
	 * @param string|callable $handler
	 * @param array<string|MiddlewareInterface> $middleware
	 * @param string|null $namespace
	 * @param string|null $scheme
	 * @param array<string> $hostnames
	 * @param array<string,mixed> $attributes
	 * @return Route
	 */
	public function put(
		string $path,
		string|callable $handler,
		array $middleware = [],
		?string $scheme = null,
		array $hostnames = [],
		array $attributes = []): Route
	{
		return $this->add(["PUT"], $path, $handler, $middleware, $scheme, $hostnames, $attributes);
	}

	/**
	 * Add a PATCH route.
	 *
	 * @param string $path
	 * @param string|callable $handler
	 * @param array<string|MiddlewareInterface> $middleware
	 * @param string|null $namespace
	 * @param string|null $scheme
	 * @param array<string> $hostnames
	 * @param array<string,mixed> $attributes
	 * @return Route
	 */
	public function patch(
		string $path,
		string|callable $handler,
		array $middleware = [],
		?string $scheme = null,
		array $hostnames = [],
		array $attributes = []): Route
	{
		return $this->add(["PATCH"], $path, $handler, $middleware, $scheme, $hostnames, $attributes);
	}

	/**
	 * Add a DELETE route.
	 *
	 * @param string $path
	 * @param string|callable $handler
	 * @param array<string|MiddlewareInterface> $middleware
	 * @param string|null $namespace
	 * @param string|null $scheme
	 * @param array<string> $hostnames
	 * @param array<string,mixed> $attributes
	 * @return Route
	 */
	public function delete(
		string $path,
		string|callable $handler,
		array $middleware = [],
		?string $scheme = null,
		array $hostnames = [],
		array $attributes = []): Route
	{
		return $this->add(["DELETE"], $path, $handler, $middleware, $scheme, $hostnames, $attributes);
	}

	/**
	 * Add a HEAD route.
	 *
	 * @param string $path
	 * @param string|callable $handler
	 * @param array<string|MiddlewareInterface> $middleware
	 * @param string|null $namespace
	 * @param string|null $scheme
	 * @param array<string> $hostnames
	 * @param array<string,mixed> $attributes
	 * @return Route
	 */
	public function head(
		string $path,
		string|callable $handler,
		array $middleware = [],
		?string $scheme = null,
		array $hostnames = [],
		array $attributes = []): Route
	{
		return $this->add(["HEAD"], $path, $handler, $middleware, $scheme, $hostnames, $attributes);
	}

	/**
	 * Add an OPTIONS route.
	 *
	 * @param string $path
	 * @param string|callable $handler
	 * @param array<string|MiddlewareInterface> $middleware
	 * @param string|null $namespace
	 * @param string|null $scheme
	 * @param array<string> $hostnames
	 * @param array<string,mixed> $attributes
	 * @return Route
	 */
	public function options(
		string $path,
		string|callable $handler,
		array $middleware = [],
		?string $scheme = null,
		array $hostnames = [],
		array $attributes = []): Route
	{
		return $this->add(["OPTIONS"], $path, $handler, $middleware, $scheme, $hostnames, $attributes);
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
		$previous_config = $this->config;

		$this->config = [
			"scheme"=> $scheme ?? $this->config["scheme"] ?? null,
			"hostnames" => $hostnames ?: $this->config["hostnames"],
			"prefix" => $prefix ?? $this->config["prefix"] ?? null,
			"namespace" => $namespace ?? $this->config["namespace"] ?? null,
			"middleware" => \array_merge(
				$this->config["middleware"],
				$middleware
			),
			"attributes" => $attributes ?: $this->config["attributes"]
		];

		// Process routes in closure
		\call_user_func($routes, $this);

		// Restore previous config
		$this->config = $previous_config;
	}
}