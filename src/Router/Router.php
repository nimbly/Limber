<?php

namespace Nimbly\Limber\Router;

use Closure;
use Nimbly\Limber\Router\Engines\DefaultRouter;
use Psr\Http\Message\ServerRequestInterface;

class Router
{
	/**
	 * @var array<string,mixed>
	 */
	protected array $config = [
		"scheme" => null,
		"host" => null,
		"prefix" => null,
		"namespace" => null,
		"middleware" => []
	];

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
	 * Route strategy engine.
	 *
	 * @var RouterInterface
	 */
	protected $engine;

	/**
	 * Router constructor.
	 *
	 * @param array<Route> $routes
	 * @param RouterInterface|null $engine
	 */
	public function __construct(
		array $routes = [],
		RouterInterface $engine = null)
	{
		if( empty($engine) ){
			$engine = new DefaultRouter;
		}

		$this->engine = $engine;
		$this->load($routes);
	}

	/**
	 * Set a regex pattern.
	 *
	 * @param string $name
	 * @param string $regex
	 * @return void
	 */
	public static function setPattern(string $name, string $regex): void
	{
		static::$patterns[$name] = $regex;
	}

	/**
	 * Get a regex pattern by name.
	 *
	 * @param string $name
	 * @return string|null
	 */
	public static function getPattern(string $name): ?string
	{
		if( \array_key_exists($name, static::$patterns) ){
			return static::$patterns[$name];
		}

		return null;
	}

	/**
	 * Load routes into router.
	 *
	 * @param array<Route> $routes
	 * @return void
	 */
	public function load(array $routes): void
	{
		$this->engine->load($routes);
	}

	/**
	 * Resolve a request into a matching route.
	 *
	 * @param ServerRequestInterface $request
	 * @return Route|null
	 */
	public function resolve(ServerRequestInterface $request): ?Route
	{
		return $this->engine->resolve($request);
	}

	/**
	 * Get all registered routes.
	 *
	 * @return array<Route>
	 */
	public function getRoutes(): array
	{
		return $this->engine->getRoutes();
	}

	/**
	 * Get the HTTP methods supported by a particular path.
	 *
	 * @param ServerRequestInterface $request
	 * @return array<string>
	 */
	public function getMethods(ServerRequestInterface $request): array
	{
		return $this->engine->getMethods($request);
	}

	/**
	 * Add a route.
	 *
	 * @param array<string> $methods Accepted HTTP methods for route.
	 * @param string $path Path or endpoint. For example (eg, "/books/{id}")
	 * @param string|callable $handler The route handler as a string (eg, "\Fully\Namespaced\Class@methodName") or a callable.
	 * @return Route
	 */
	public function add(array $methods, string $path, string|callable $handler): Route
	{
		return $this->engine->add($methods, $path, $handler, $this->config);
	}

	/**
	 * Add a GET route.
	 *
	 * @param string $path
	 * @param string|callable $handler
	 * @return Route
	 */
	public function get(string $path, string|callable $handler): Route
	{
		return $this->add(["GET", "HEAD"], $path, $handler);
	}

	/**
	 * Add a POST route.
	 *
	 * @param string $path
	 * @param string|callable $handler
	 * @return Route
	 */
	public function post(string $path, string|callable $handler): Route
	{
		return $this->add(["POST"], $path, $handler);
	}

	/**
	 * Add a PUT route.
	 *
	 * @param string $path
	 * @param string|callable $handler
	 * @return Route
	 */
	public function put(string $path, string|callable $handler): Route
	{
		return $this->add(["PUT"], $path, $handler);
	}

	/**
	 * Add a PATCH route.
	 *
	 * @param string $path
	 * @param string|callable $handler
	 * @return Route
	 */
	public function patch(string $path, string|callable $handler): Route
	{
		return $this->add(["PATCH"], $path, $handler);
	}

	/**
	 * Add a DELETE route.
	 *
	 * @param string $path
	 * @param string|callable $handler
	 * @return Route
	 */
	public function delete(string $path, string|callable $handler): Route
	{
		return $this->add(["DELETE"], $path, $handler);
	}

	/**
	 * Add a HEAD route.
	 *
	 * @param string $path
	 * @param string|callable $handler
	 * @return Route
	 */
	public function head(string $path, string|callable $handler): Route
	{
		return $this->add(["HEAD"], $path, $handler);
	}

	/**
	 * Add an OPTIONS route.
	 *
	 * @param string $path
	 * @param string|callable $handler
	 * @return Route
	 */
	public function options(string $path, string|callable $handler): Route
	{
		return $this->add(["OPTIONS"], $path, $handler);
	}

	/**
	 * Group routes together with a set of shared configuration options.
	 *
	 * @param array<string,mixed> $groupConfig
	 * @param Closure $callback
	 * @return void
	 */
	public function group(array $groupConfig, Closure $callback): void
	{
		// Save current config
		$previousConfig = $this->config;

		// Merge group config values with current config
		$this->config = $this->mergeGroupConfig($this->config, $groupConfig);

		// Process routes in closure
		\call_user_func($callback, $this);

		// Restore previous config
		$this->config = $previousConfig;
	}

	/**
	 * Merge parent config in with group config.
	 *
	 * @param array<string,mixed> $parentConfig
	 * @param array<string,mixed> $groupConfig
	 * @return array<string,mixed>
	 */
	protected function mergeGroupConfig(array $parentConfig, array $groupConfig): array
	{
		return [
			"scheme"=> $groupConfig["scheme"] ?? $parentConfig["scheme"] ?? null,
			"hostname" => $groupConfig["hostname"] ?? $parentConfig["hostname"] ?? null,
			"prefix" => $groupConfig["prefix"] ?? $parentConfig["prefix"] ?? null,
			"namespace" => $groupConfig["namespace"] ?? $parentConfig["namespace"] ?? null,
			"middleware" => \array_merge(
				$parentConfig["middleware"] ?? [],
				$groupConfig["middleware"] ?? []
			)
		];
	}
}