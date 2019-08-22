<?php

namespace Limber\Router;

use Psr\Http\Message\ServerRequestInterface;

abstract class RouterAbstract
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
     * Router constructor.
     * @param array<Route>|null $routes
     */
    abstract public function __construct(array $routes = null);

    /**
     * Add a route
     *
     * @param array $methods
     * @param string $uri
     * @param string|callable $target
     * @return Route
     */
    abstract public function add(array $methods, string $uri, $target): Route;

    /**
     * Resolve the matching Route.
     *
     * @param ServerRequestInterface $request
     * @return Route|null
     */
    abstract public function resolve(ServerRequestInterface $request): ?Route;

    /**
     * Return all HTTP methods supported by the endpoint.
     *
     * @param ServerRequestInterface $request
     * @return array<string>
     */
    abstract public function getMethodsForUri(ServerRequestInterface $request): array;

    /**
     * Create/overwrite a regex pattern
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
     * Get a regex pattern by name
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
     * @param string $uri
     * @param string|callable $target
     * @return Route
     */
    public function get($uri, $target): Route
    {
        return $this->add(["GET"], $uri, $target);
    }

    /**
     * @param string $uri
     * @param string|callable $target
     * @return Route
     */
    public function post($uri, $target): Route
    {
        return $this->add(["POST"], $uri, $target);
    }

    /**
     * @param string $uri
     * @param string|callable $target
     * @return Route
     */
    public function put($uri, $target): Route
    {
        return $this->add(["PUT"], $uri, $target);
    }

    /**
     * @param string $uri
     * @param string|callable $target
     * @return Route
     */
    public function patch($uri, $target): Route
    {
        return $this->add(["PATCH"], $uri, $target);
    }

    /**
     * @param string $uri
     * @param string|callable $target
     * @return Route
     */
    public function delete(string $uri, $target): Route
    {
        return $this->add(["DELETE"], $uri, $target);
    }

    /**
     * @param string $uri
     * @param string|callable $target
     * @return Route
     */
    public function head(string $uri, $target): Route
    {
        return $this->add(["HEAD"], $uri, $target);
    }

    /**
     * @param string $uri
     * @param string|callable $target
     * @return Route
     */
    public function options(string $uri, $target): Route
    {
        return $this->add(["OPTIONS"], $uri, $target);
    }

    /**
     * @param array $config
     * @param \Closure $callback
     * @return void
     */
    public function group(array $config, \Closure $callback): void
    {
        // Save current config
        $previousConfig = $this->config;

        // Merge config values
        $this->config = $this->mergeGroupConfig($config);

        // Process routes in closure
        \call_user_func($callback, $this);

        // Restore previous config
        $this->config = $previousConfig;
    }

    /**
     * Merge parent route Group configs in with child group.
     *
     * @param array<string, mixed> $groupConfig
     * @return array<string, mixed>
     */
    protected function mergeGroupConfig(array $groupConfig): array
    {
        $config = $this->config;

        $config['hostname'] = $groupConfig['hostname'] ?? null;
        $config['prefix'] = $groupConfig['prefix'] ?? null;
        $config['namespace'] = $groupConfig['namespace'] ?? null;

        if( \array_key_exists('middleware', $groupConfig) ){

            if( \array_key_exists('middleware', $config) ){
                $config['middleware'] = \array_merge($config['middleware'], $groupConfig['middleware']);
            }

            else {
                $config['middleware'] = $groupConfig['middleware'];
            }
        }

        return $config;
    }
}