<?php

namespace Limber\Router;

use Limber\Exceptions\RouteException;
use Psr\Http\Server\MiddlewareInterface;

class Route
{
	/**
	 * Path to match
	 *
	 * @var string
	 */
	protected string $path;

	/**
	 * Methods to match
	 *
	 * @var array<string>
	 */
	protected array $methods = [];

	/**
	 * Route handler
	 *
	 * @var string|callable
	 */
	protected mixed $handler;

	/**
	 * Request scheme (http or https)
	 *
	 * @var array<string>
	 */
	protected array $schemes = [];

	/**
	 * Request hostname
	 *
	 * @var array<string>
	 */
	protected array $hostnames = [];

	/**
	 * Controller namespace prefix
	 *
	 * @var string|null
	 */
	protected ?string $namespace;

	/**
	 * Request prefix
	 *
	 * @var string|null
	 */
	protected ?string $prefix;

	/**
	 * Route middlware to apply.
	 *
	 * @var array<class-string>|array<MiddlewareInterface>
	 */
	protected array $middleware = [];

	/**
	 * Route attributes.
	 *
	 * @var array<string,mixed>
	 */
	protected array $attributes = [];

	/**
	 * Pattern parts
	 *
	 * @var array<string>
	 */
	protected array $patternParts = [];

	/**
	 * Named path params in route eg: "id" in books/{id}/authors
	 *
	 * @var array<string>
	 */
	private array $namedPathParameters = [];


	/**
	 * Route constructor.
	 *
	 * @param string|array<string> $methods
	 * @param string $path
	 * @param string|callable $handler
	 * @param array<string,mixed> $config Additional config data (usually passed in from group settings)
	 *
	 */
	public function __construct(
		string|array $methods,
		string $path,
		string|callable $handler,
		array $config = [])
	{
		// Required bits
		$this->methods = \array_map("\strtoupper", \is_string($methods) ? [$methods] : $methods);
		$this->handler = $handler;
		$this->setPath($path);

		// Optional
		$this->setSchemes($config["scheme"] ?? []);
		$this->setHostnames($config["hostname"] ?? []);
		$this->setMiddleware($config["middleware"] ?? []);
		$this->setPrefix($config["prefix"] ?? "");
		$this->setNamespace($config["namespace"] ?? "");
		$this->setAttributes($config["attributes"] ?? []);
	}

	/**
	 * Set the path/endpoint for this route.
	 *
	 * @param string $path
	 * @return void
	 */
	public function setPath(string $path): void
	{
		$this->path = $path;

		$namedPathParameters = [];
		$patternParts = [];

		foreach( \explode("/", \trim($path, "/")) as $part ){

			if( \preg_match("/{([a-z0-9_]+)(?:\:([a-z0-9_]+))?}/i", $part, $match) ){

				if( \in_array($match[1], $namedPathParameters) ){
					throw new RouteException("Path parameter \"{$match[1]}\" already defined for route {$match[0]}");
				}

				// Predefined pattern
				if( isset($match[2]) ){

					$part = Router::getPattern($match[2]);

					if( empty($part) ){
						throw new RouteException("Router pattern not found: {$match[2]}");
					}
				}

				// Match anything
				else {

					$part = "[^\/]+";
				}

				$part = "({$part})";

				// Save the named path param
				$namedPathParameters[] = $match[1];
			}

			$patternParts[] = $part;
		}

		$this->namedPathParameters = $namedPathParameters;
		$this->patternParts = $patternParts;
	}

	/**
	 *
	 * SETters
	 */

	/**
	 * @param string|array<string> $schemes Possible values are "http" or "https"
	 * @return Route
	 */
	public function setSchemes(string|array $schemes): Route
	{
		if( !\is_array($schemes) ){
			$schemes = [$schemes];
		}

		$this->schemes = $schemes;
		return $this;
	}

	/**
	 * @param string|array<string> $hostnames
	 * @return Route
	 */
	public function setHostnames(string|array $hostnames): Route
	{
		if( !\is_array($hostnames) ){
			$hostnames = [$hostnames];
		}

		$this->hostnames = $hostnames;

		return $this;
	}

	/**
	 * @param string $prefix
	 * @return Route
	 */
	public function setPrefix(string $prefix): Route
	{
		$this->prefix = \trim($prefix, "/");

		return $this;
	}

	/**
	 * Apply middleware to this route
	 *
	 * @param class-string|array<class-string>|array<MiddlewareInterface> $middleware
	 * @return Route
	 */
	public function setMiddleware(string|array $middleware): Route
	{
		if( !\is_array($middleware) ){
			$middleware = [$middleware];
		}

		$this->middleware = \array_merge($this->middleware, $middleware);

		return $this;
	}

	/**
	 * Set the controller namespace.
	 *
	 * @param string $namespace
	 * @return Route
	 */
	public function setNamespace(string $namespace): Route
	{
		$this->namespace = $namespace;
		return $this;
	}

	/**
	 * Set an attribute on the route.
	 *
	 * @param string $attribute
	 * @param mixed $value
	 * @return Route
	 */
	public function setAttribute(string $attribute, mixed $value): Route
	{
		$this->attributes[$attribute] = $value;
		return $this;
	}

	/**
	 * Set the attributes for the route.
	 *
	 * @param array<string,mixed> $attributes
	 * @return Route
	 */
	public function setAttributes(array $attributes): Route
	{
		$this->attributes = $attributes;
		return $this;
	}


	/**
	 *
	 * GETters
	 *
	 */

	/**
	 * Get the full path.
	*
	* @return string
	*/
	public function getPath(): string
	{
		if( $this->prefix ){
			return "{$this->prefix}/{$this->path}";
		}

		return $this->path;
	}

	/**
	 * Get all schemes this route responds to.
	 *
	 * @return array
	 */
	public function getSchemes(): array
	{
		return $this->schemes;
	}

	/**
	 * Get all hostnames this route responds to.
	 *
	 * @return array
	 */
	public function getHostnames(): array
	{
		return $this->hostnames;
	}

	/**
	 * Get all methods this route respondes to.
	 *
	 * @return array<string>
	 */
	public function getMethods(): array
	{
		return $this->methods;
	}

	/**
	 * Get route handler.
	 *
	 * @return string|callable
	 */
	public function getHandler(): string|callable
	{
		if( \is_string($this->handler) && $this->namespace ){

			return \sprintf(
				"%s\\%s",
				\trim($this->namespace, "\\"),
				$this->handler
			);
		}

		return $this->handler;
	}

	/**
	 * Get the path prefix.
	 *
	 * @return string
	 */
	public function getPrefix(): ?string
	{
		return $this->prefix;
	}

	/**
	 * Get all middleware this route should apply.
	 *
	 * @return array<string|MiddlewareInterface>
	 */
	public function getMiddleware(): array
	{
		return $this->middleware;
	}

	/**
	 * Get namespace of route
	 *
	 * @return string|null
	 */
	public function getNamespace(): ?string
	{
		return $this->namespace;
	}

	/**
	 * Get an attribute from the route.
	 *
	 * @param string $attribute
	 * @return mixed
	 */
	public function getAttribute(string $attribute)
	{
		return $this->attributes[$attribute] ?? null;
	}

	/**
	 * Does the route have the given attribute.
	 *
	 * @param string $attribute
	 * @return boolean
	 */
	public function hasAttribute(string $attribute): bool
	{
		return \array_key_exists($attribute, $this->attributes);
	}

	/**
	 * Get all the attributes of the route.
	 *
	 * @return array<string,mixed>
	 */
	public function getAttributes(): array
	{
		return $this->attributes;
	}

	/**
	 * Get the regex pattern used to match this route.
	 *
	 * @return array<string>
	 */
	public function getPatternParts(): array
	{
		return $this->patternParts;
	}

	/**
	 * Extract the path parameters for the given path.
	 *
	 * Makes a key => value pair of path part names and their value.
	 *
	 * Eg.
	 *
	 * The route "books/{id}" with an actual path of "books/1234"
	 * will return:
	 *
	 * [
	 *      "id" => "1234",
	 * ]
	 *
	 * These named path params are used during Dependency Injection resolution
	 * in the Kernel to pass off to the matched method parameter.
	 *
	 * @param string $path
	 * @return array<string, string>
	 */
	public function getPathParams(string $path): array
	{
		$pathParams = [];

		// Build out the regex
		$pattern = \implode("\/", $this->getPatternParts());

		if( \preg_match("/^{$pattern}$/", \trim($path, "/"), $parts) ){

			// Grab all but the first match, because that will always be the full string.
			foreach( \array_slice($parts, 1, \count($parts) - 1) as $i => $param ) {
				$pathParams[$this->namedPathParameters[$i]] = $param;
			}

		}

		return $pathParams;
	}

	/**
	 *
	 * MATCHing
	 *
	 */

	/**
	 * Does the given scheme match this route's scheme?
	 *
	 * @param string $scheme
	 * @return bool
	 */
	public function matchScheme($scheme): bool
	{
		if( empty($this->schemes) ){
			return true;
		}

		return \array_search(\strtolower($scheme), \array_map("strtolower", $this->schemes)) !== false;
	}


	/**
	 * Does the given hostname match the route's hostname?
	 *
	 * @param string $hostnames
	 * @return bool
	 */
	public function matchHostname(string $hostnames): bool
	{
		if( empty($this->hostnames) ){
			return true;
		}

		return \array_search(
			\strtolower($hostnames),
			\array_map("\strtolower", $this->hostnames)
		) !== false;
	}

	/**
	 * Does the given method match the route's set of methods?
	 *
	 * @param string $method
	 * @return bool
	 */
	public function matchMethod(string $method): bool
	{
		return \in_array(\strtoupper($method), $this->methods);
	}

	/**
	 * Does the given path match the route's path?
	 *
	 * @param string $path
	 * @return bool
	 */
	public function matchPath(string $path): bool
	{
		$pattern = \implode("\/", $this->patternParts);
		return \preg_match("/^{$pattern}$/i", \trim($path, "/")) != false;
	}
}