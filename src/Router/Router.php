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
	 * @var array{scheme:string|null,prefix:string|null,namespace:string|null,hostnames:array<string>,middleware:array<MiddlewareInterface|class-string>,attributes:array<string,mixed>}
	 */
	protected array $group_config = [
		"scheme" => null,
		"prefix" => null,
		"namespace" => null,
		"hostnames" => [],
		"middleware" => [],
		"attributes" => [],
	];

	protected static array $resourceConfig = [
		"handler_suffix" => "Handler",
		"update_method" => "PUT",
		"identifier_pattern" => "uuid"
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
	 * Get all routes.
	 *
	 * Routes are indexed by HTTP method, for faster lookup and matching.
	 *
	 * @return array<string,array<Route>>
	 */
	public function getRoutes(): array
	{
		return $this->routes;
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
	 * Set the resource configuration options.
	 *
	 * @param string $handler_suffix The suffix applied to the class name. For example: FooHandler, where "Handler" is the suffix.
	 * @param string $update_method HTTP method to use for updating a resource (typically PUT or PATCH).
	 * @param string|null $identifier_pattern Default pattern to use for resource identifiers. Eg, uuid or int.
	 * @return void
	 */
	public static function setResourceConfig(
		string $handler_suffix = "Handler",
		string $update_method = "PUT",
		?string $identifier_pattern = "uuid"): void
	{
		self::$resourceConfig = [
			"handler_suffix" => $handler_suffix,
			"update_method" => $update_method,
			"identifier_pattern" => $identifier_pattern
		];
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
	 * @param string $path The URI path this route will respond to.
	 * @param string|callable $handler The handler for this route.
	 * @param string|null $scheme The scheme this route responds to (http or https). Null responds to any.
	 * @param array<string> $hostnames Hostnames this route responds to.
	 * @param array<MiddlewareInterface|class-string> $middleware Middleware to be applied to this route.
	 * @param array<string,mixed> $attributes Key value pair of attributes to be passed to ServerRequestInterface instance.
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
	 * @param string $path The URI path this route will respond to.
	 * @param string|callable $handler The handler for this route.
	 * @param string|null $scheme The scheme this route responds to (http or https). Null responds to any.
	 * @param array<string> $hostnames Hostnames this route responds to.
	 * @param array<MiddlewareInterface|class-string> $middleware Middleware to be applied to this route.
	 * @param array<string,mixed> $attributes Key value pair of attributes to be passed to ServerRequestInterface instance.
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
	 * @param string $path The URI path this route will respond to.
	 * @param string|callable $handler The handler for this route.
	 * @param string|null $scheme The scheme this route responds to (http or https). Null responds to any.
	 * @param array<string> $hostnames Hostnames this route responds to.
	 * @param array<MiddlewareInterface|class-string> $middleware Middleware to be applied to this route.
	 * @param array<string,mixed> $attributes Key value pair of attributes to be passed to ServerRequestInterface instance.
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
	 * @param string $path The URI path this route will respond to.
	 * @param string|callable $handler The handler for this route.
	 * @param string|null $scheme The scheme this route responds to (http or https). Null responds to any.
	 * @param array<string> $hostnames Hostnames this route responds to.
	 * @param array<MiddlewareInterface|class-string> $middleware Middleware to be applied to this route.
	 * @param array<string,mixed> $attributes Key value pair of attributes to be passed to ServerRequestInterface instance.
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
	 * @param string $path The URI path this route will respond to.
	 * @param string|callable $handler The handler for this route.
	 * @param string|null $scheme The scheme this route responds to (http or https). Null responds to any.
	 * @param array<string> $hostnames Hostnames this route responds to.
	 * @param array<MiddlewareInterface|class-string> $middleware Middleware to be applied to this route.
	 * @param array<string,mixed> $attributes Key value pair of attributes to be passed to ServerRequestInterface instance.
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
	 * @param string $path The URI path this route will respond to.
	 * @param string|callable $handler The handler for this route.
	 * @param string|null $scheme The scheme this route responds to (http or https). Null responds to any.
	 * @param array<string> $hostnames Hostnames this route responds to.
	 * @param array<MiddlewareInterface|class-string> $middleware Middleware to be applied to this route.
	 * @param array<string,mixed> $attributes Key value pair of attributes to be passed to ServerRequestInterface instance.
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
	 * @param string $path The URI path this route will respond to.
	 * @param string|callable $handler The handler for this route.
	 * @param string|null $scheme The scheme this route responds to (http or https). Null responds to any.
	 * @param array<string> $hostnames Hostnames this route responds to.
	 * @param array<MiddlewareInterface|class-string> $middleware Middleware to be applied to this route.
	 * @param array<string,mixed> $attributes Key value pair of attributes to be passed to ServerRequestInterface instance.
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
	 * Add routes for a resource.
	 *
	 * This is a shortcut method to add standard CRUD routes for a resource.
	 * Several assumptions are made with regards to the path and the handler
	 * name.
	 *
	 * @param string $name The resource name. This name may include a base URL that will be included in all endpoints.
	 * @param array<ResourceAction> $actions Which HTTP actions should be supported for this resource.
	 * @param string|null $identifier Override the default identifier type set with Router::setResourceConfig().
	 * @return array
	 */
	public function resource(
		string $name,
		array $actions = [ResourceAction::get, ResourceAction::list, ResourceAction::create, ResourceAction::update, ResourceAction::delete],
		?string $identifier_pattern = null): array
	{
		$path = \strrpos($name, "/");

		if( $path !== false ){
			$resource = \trim(\substr($name, $path), "/");
			$base_url = \trim(\substr($name, 0, $path), "/") . "/" . $resource;
			$name = $resource;
		}
		else {
			$base_url = \trim($name, "/");
		}

		$handler = \ucfirst($name) . self::$resourceConfig["handler_suffix"];

		$routes = [];

		if( \in_array(ResourceAction::list, $actions) ){
			$routes[] = $this->get($base_url, "{$handler}@list");
		}

		if( \in_array(ResourceAction::create, $actions) ){
			$routes[] = $this->post($base_url, "{$handler}@create");
		}

		$identifier_pattern ??= self::$resourceConfig["identifier_pattern"];
		$id = $identifier_pattern ? "{id:{$identifier_pattern}}" : "{id}";

		if( \in_array(ResourceAction::get, $actions) ){
			$routes[] = $this->get("{$base_url}/{$id}", "{$handler}@get");
		}

		if( \in_array(ResourceAction::update, $actions) ){
			$routes[] = $this->add([self::$resourceConfig["update_method"]], "{$base_url}/{$id}", "{$handler}@update");
		}

		if( \in_array(ResourceAction::delete, $actions) ){
			$routes[] = $this->delete("{$base_url}/{$id}", "{$handler}@delete");
		}

		return $routes;
	}

	/**
	 * Group routes together with a set of shared configuration options.
	 *
	 * @param callable $routes A callable that accepts the Router instance.
	 * @param string|null $namespace Namespace prepended to all string based handlers.
	 * @param string|null $prefix URI prefix predended to all paths.
	 * @param string|null $scheme Scheme (https or https) that routes will respond to. Null responds to any.
	 * @param array<MiddlewareInterface|class-string> $middleware Middleware to be applied to all routes.
	 * @param array<string> $hostnames Hostnames that routes will respond to.
	 * @param array<string,mixed> $attributes Key value pair of attributes that will be passed to ServerRequestInterface instance.
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