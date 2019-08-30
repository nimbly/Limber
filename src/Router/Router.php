<?php

namespace Limber\Router;

use Limber\Router\Engines\DefaultRouter;
use Psr\Http\Message\ServerRequestInterface;

class Router
{
    /**
     * @var array
     */
    protected $config = [
        'scheme' => null,
        'host' => null,
        'prefix' => null,
        'namespace' => null,
        'middleware' => [],
    ];

    /**
     * Route path parameter patterns
     *
     * @var array
     */
    protected static $patterns = [
        'alpha' => '[a-z]+',
        'int' => '\d+',
        'alphanumeric' => '[a-z0-9]+',
        'uuid' => '[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}',
        'hex' => '[a-f0-9]+',
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
	 * @param RouterInterface $engine
     */
	public function __construct(array $routes = [], RouterInterface $engine = null)
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
	 * @param array $routes
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
	 * @return array
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
	 * @param array<string> $methods
	 * @param string $path
	 * @param string|callable $action
	 * @return Route
	 */
	public function add(array $methods, string $path, $action): Route
	{
		return $this->engine->add($methods, $path, $action, $this->config);
	}

    /**
	 * Add a GET route.
	 *
     * @param string $path
     * @param string|callable $action
     * @return Route
     */
    public function get($path, $action): Route
    {
        return $this->add(["GET"], $path, $action);
    }

    /**
	 * Add a POST route.
	 *
     * @param string $path
     * @param string|callable $action
     * @return Route
     */
    public function post($path, $action): Route
    {
        return $this->add(["POST"], $path, $action);
    }

    /**
	 * Add a PUT route.
	 *
     * @param string $path
     * @param string|callable $action
     * @return Route
     */
    public function put($path, $action): Route
    {
        return $this->add(["PUT"], $path, $action);
    }

    /**
	 * Add a PATCH route.
	 *
     * @param string $path
     * @param string|callable $action
     * @return Route
     */
    public function patch($path, $action): Route
    {
        return $this->add(["PATCH"], $path, $action);
    }

    /**
	 * Add a DELETE route.
	 *
     * @param string $path
     * @param string|callable $action
     * @return Route
     */
    public function delete(string $path, $action): Route
    {
        return $this->add(["DELETE"], $path, $action);
    }

    /**
	 * Add a HEAD route.
	 *
     * @param string $path
     * @param string|callable $action
     * @return Route
     */
    public function head(string $path, $action): Route
    {
        return $this->add(["HEAD"], $path, $action);
    }

    /**
	 * Add an OPTIONS route.
	 *
     * @param string $path
     * @param string|callable $action
     * @return Route
     */
    public function options(string $path, $action): Route
    {
        return $this->add(["OPTIONS"], $path, $action);
    }

    /**
	 * Group routes together with a set of shared configuration options.
	 *
     * @param array $groupConfig
     * @param \closure $callback
     * @return void
     */
    public function group(array $groupConfig, \closure $callback): void
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
     * Merge parent route Group configs in with child group.
     *
	 * @param array<string, mixed> $config
     * @param array<string, mixed> $groupConfig
     * @return array<string, mixed>
     */
    protected function mergeGroupConfig(array $config, array $groupConfig): array
    {
		return [
			'scheme'=> $groupConfig['scheme'] ?? $config['scheme'] ?? null,
			'hostname' => $groupConfig['hostname'] ?? $config['hostname'] ?? null,
			'prefix' => $groupConfig['prefix'] ?? $config['pregix'] ?? null,
			'namespace' => $groupConfig['namespace'] ?? $config['namespace'] ?? null,
			'middleware' => \array_merge(
				$config['middleware'] ?? [],
				$groupConfig['middleware'] ?? []
			)
		];
    }
}