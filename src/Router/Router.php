<?php

namespace Limber\Router;

use Symfony\Component\HttpFoundation\Request;

class Router
{
    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var Route[]
     */
    protected $routes = [];

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
     * @param Route[]|null $routes
     */
    public function __construct(array $routes = null)
    {
        if( $routes ){
            $this->routes = $routes;
        }
    }

    /**
     * Create/overwrite a regex pattern
     *
     * @param $name
     * @param $regex
     */
    public static function setPattern($name, $regex)
    {
        static::$patterns[$name] = $regex;
    }

    /**
     * Get a regex pattern by name
     *
     * @param $name
     * @return mixed|null
     */
    public static function getPattern($name)
    {
        if( array_key_exists($name, static::$patterns) ){
            return static::$patterns[$name];
        }

        return null;
    }

    /**
     * Add a route
     *
     * @param $methods
     * @param $uri
     * @param $target
     * @return Route
     */
    public function add($methods, $uri, $target)
    {
        if( !is_array($methods) ){
            $methods = [$methods];
        }

        $route = new Route($methods, $uri, $target, $this->config);
        $this->routes[] = $route;

        return $route;
    }

    /**
     * @param $uri
     * @param $target
     * @return Route
     */
    public function get($uri, $target)
    {
        return $this->add(Request::METHOD_GET, $uri, $target);
    }

    /**
     * @param $uri
     * @param $target
     * @return Route
     */
    public function post($uri, $target)
    {
        return $this->add(Request::METHOD_POST, $uri, $target);
    }

    /**
     * @param $uri
     * @param $target
     * @return Route
     */
    public function put($uri, $target)
    {
        return $this->add(Request::METHOD_PUT, $uri, $target);
    }

    /**
     * @param $uri
     * @param $target
     * @return Route
     */
    public function patch($uri, $target)
    {
        return $this->add(Request::METHOD_PATCH, $uri, $target);
    }

    /**
     * @param $uri
     * @param $target
     * @return Route
     */
    public function delete($uri, $target)
    {
        return $this->add(Request::METHOD_DELETE, $uri, $target);
    }

    /**
     * @param $uri
     * @param $target
     * @return Route
     */
    public function head($uri, $target)
    {
        return $this->add(Request::METHOD_HEAD, $uri, $target);
    }

    /**
     * @param $uri
     * @param $target
     * @return Route
     */
    public function options($uri, $target)
    {
        return $this->add(Request::METHOD_OPTIONS, $uri, $target);
    }

    /**
     * @param Request $request
     * @return bool|Route
     */
    public function resolve(Request $request)
    {
        // Loop through routes and find match
        foreach( $this->routes as $route )
        {
            if( $route->matchMethod($request->getMethod()) &&
                $route->matchScheme($request->getScheme()) &&
                $route->matchHost($request->getHost()) &&
                $route->matchUri($request->getPathInfo()) ){

                return $route;
            }
        }

        return false;
    }

    /**
     * @param Request $request
     * @return array
     */
    public function getMethodsForUri(Request $request)
    {
        $methods = [];

        foreach( $this->routes as $route )
        {
            if( $route->matchScheme($request->getScheme()) &&
                $route->matchHost($request->getHost()) &&
                $route->matchUri($request->getPathInfo()) ){
                $methods = array_merge($methods, $route->methods);
            }
        }

        return array_unique($methods);
    }

    /**
     * Get the routes
     * @return Route[]|null
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * @param array $config
     * @param \Closure $callback
     */
    public function group(array $config, \Closure $callback)
    {
        // Save current config
        $previousConfig = $this->config;

        // Merge config values
        $this->config = $this->mergeGroupConfig($config);

        // Process routes in closure
        $callback($this);

        // Restore previous config
        $this->config = $previousConfig;
    }

    /**
     * @param $groupConfig
     * @return array
     */
    protected function mergeGroupConfig($groupConfig)
    {
        $config = $this->config;

        if( array_key_exists('hostname', $groupConfig) ){
            $config['hostname'] = $groupConfig['hostname'];
        }

        if( array_key_exists('prefix', $groupConfig) ){
            $config['prefix'] = $groupConfig['prefix'];
        }

        if( array_key_exists('namespace', $groupConfig) ){
            $config['namespace'] = $groupConfig['namespace'];
        }

        if( array_key_exists('middleware', $groupConfig) ){

            if( array_key_exists('middleware', $config) ){
                $config['middleware'] = array_merge($config['middleware'], $groupConfig['middleware']);
            }

            else {
                $config['middleware'] = $groupConfig['middleware'];
            }
        }

        return $config;
    }
}