<?php

namespace Nimbly\Limber\Router;

use Nimbly\Limber\Exceptions\RouteException;
use Psr\Http\Server\MiddlewareInterface;

class Route
{
	/**
	 * @param array<string> $methods HTTP methods route responds to.
	 * @param string $path Path in a regular expression format.
	 * @param string|callable $handler
	 * @param array<string|MiddlewareInterface> $middleware
	 * @param string|null $scheme
	 * @param array<string> $hostnames
	 * @param array<string,mixed> $attributes
	 */
	public function __construct(
		protected array $methods,
		protected string $path,
		protected mixed $handler,
		protected array $middleware = [],
		protected ?string $scheme = null,
		protected array $hostnames = [],
		protected array $attributes = [],
	)
	{
		$this->methods = \array_map("\\strtoupper", $methods);
		$this->scheme = $this->scheme ? \strtolower($this->scheme) : null;
		$this->hostnames = \array_map("\\strtolower", $this->hostnames);
	}

	/**
	 * Get all methods this route responds to.
	 *
	 * @return array<string>
	 */
	public function getMethods(): array
	{
		return $this->methods;
	}

	/**
	 * Get the path for this route.
	 *
	 * @return string
	 */
	public function getPath(): string
	{
		return $this->path;
	}

	/**
	 * Get the handler for this route.
	 *
	 * @return string|callable
	 */
	public function getHandler(): mixed
	{
		return $this->handler;
	}

	/**
	 * Get the middleware that should be applied to this route.
	 *
	 * @return array<string|MiddlewareInterface>
	 */
	public function getMiddleware(): array
	{
		return $this->middleware;
	}

	/**
	 * Get the hostnames for this route.
	 *
	 * @return array<string>
	 */
	public function getHostnames(): array
	{
		return $this->hostnames;
	}

	/**
	 * Get the HTTP scheme (http or https) for this route.
	 *
	 * @return string|null
	 */
	public function getScheme(): ?string
	{
		return $this->scheme;
	}

	/**
	 * Get attributes for route.
	 *
	 * @return array<string,mixed>
	 */
	public function getAttributes(): array
	{
		return $this->attributes;
	}

	/**
	 * Check whether route has a specific attribute.
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function hasAttribute(string $name): bool
	{
		return \array_key_exists($name, $this->attributes);
	}

	/**
	 * Get a specific attribute by name.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function getAttribute(string $name): mixed
	{
		return $this->attributes[$name] ?? null;
	}

	/**
	 * Match HTTP method.
	 *
	 * @param string $method
	 * @return boolean
	 */
	public function matchMethod(string $method): bool
	{
		return \in_array(\strtoupper($method), $this->methods);
	}

	/**
	 * Match hostname.
	 *
	 * @param string $hostname
	 * @return boolean
	 */
	public function matchHostname(string $hostname): bool
	{
		if( empty($this->hostnames) ){
			return true;
		}

		return \in_array(
			\strtolower($hostname),
			$this->hostnames
		);
	}

	/**
	 * Match the HTTP scheme.
	 *
	 * @param string $scheme
	 * @return boolean
	 */
	public function matchScheme(string $scheme): bool
	{
		if( empty($this->scheme) ){
			return true;
		}

		return \strtolower($scheme) === $this->scheme;
	}

	/**
	 * Match the given path against the route's path.
	 *
	 * @param string $path
	 * @return boolean
	 */
	public function matchPath(string $path): bool
	{
		$match = \preg_match($this->path, \trim($path, "/"));

		if( $match === false ){
			throw new RouteException("Route path regular expression is invalid.");
		}

		return (bool) $match;
	}

	/**
	 * Get all path parameters.
	 *
	 * @param string $path
	 * @return array<string,string>
	 */
	public function getPathParameters(string $path): array
	{
		if( \preg_match($this->path, \trim($path, "/"), $path_parameters) === false ){
			throw new RouteException("Regular expression is invalid.");
		}

		return \array_filter(
			$path_parameters,
			fn(int|string $key): bool => \is_string($key),
			ARRAY_FILTER_USE_KEY
		);
	}
}