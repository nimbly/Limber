<?php

namespace Nimbly\Limber\Router;

use Nimbly\Limber\Exceptions\RouteException;
use Psr\Http\Server\MiddlewareInterface;

class Route
{
	/**
	 * Compiled regular expression for path matching.
	 *
	 * @var string|null
	 */
	private ?string $path_regex = null;

	/**
	 * @param array<string> $methods HTTP methods route responds to.
	 * @param string $path Path in a regular expression format.
	 * @param string|callable $handler Handler to invoke for this route.
	 * @param string|null $scheme HTTP scheme (http or https) route responds to.
	 * @param array<string> $hostnames Array of hostnames route responds to.
	 * @param array<string|MiddlewareInterface> $middleware Array of middleware to apply when route matches.
	 * @param array<string,mixed> $attributes Array of key/value pair attributes to be passed to ServerRequestInterface instance.
	 */
	public function __construct(
		protected array $methods,
		protected string $path,
		protected mixed $handler,
		protected ?string $scheme = null,
		protected array $hostnames = [],
		protected array $middleware = [],
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
	public function getHandler(): string|callable
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
	 * Compile the regular expression for path matching.
	 *
	 * @param string $path
	 * @return string Returns compiled regular expression.
	 */
	private function compileRegexPattern(string $path): string
	{
		$parts = [];

		foreach( \explode("/", \trim($path, "/")) as $part ){

			// Is this a named parameter?
			if( \preg_match("/{([a-z0-9_]+)(?:\:([a-z0-9_]+))?}/i", $part, $match) ){

				// Predefined pattern
				if( isset($match[2]) ){
					$part = Router::getPattern($match[2]);

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
		if( empty($this->path_regex) ){
			$this->path_regex = $this->compileRegexPattern($this->path);
		}

		$match = \preg_match($this->path_regex, \trim($path, "/"));

		if( $match === false ){
			throw new RouteException("Route path regular expression is invalid.");
		}

		return (bool) $match;
	}

	/**
	 * Get all parameters from the path of a URI.
	 *
	 * @param string $path
	 * @return array<array-key,string>
	 */
	public function getPathParameters(string $path): array
	{
		if( empty($this->path_regex) ){
			$this->path_regex = $this->compileRegexPattern($this->path);
		}

		if( \preg_match($this->path_regex, \trim($path, "/"), $path_parameters) === false ){
			throw new RouteException("Route path regular expression is invalid.");
		}

		return \array_filter(
			$path_parameters,
			fn(int|string $key): bool => \is_string($key),
			ARRAY_FILTER_USE_KEY
		);
	}
}