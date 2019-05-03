<?php

namespace Limber\Router;

use Symfony\Component\HttpFoundation\Request;

class LinearRouter extends Router
{
    /**
     * @var array<Route>
     */
    protected $routes = [];

    /**
     * @inheritDoc
     */
    public function __construct(array $routes = null)
    {
        if( $routes ){
            $this->routes = $routes;
        }
    }

    /**
     * @inheritDoc
     */
    public function add($methods, string $uri, $target): Route
    {
        // Create new Route instance
        $route = new Route($methods, $uri, $target, $this->config);
        $this->routes[] = $route;
        return $route;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Request $request): ?Route
    {
        foreach( $this->routes as $route ){

            if( $route->matchUri($request->getPathInfo()) &&
                $route->matchMethod($request->getMethod()) &&
                $route->matchHostname($request->getHost()) &&
                $route->matchScheme($request->getScheme()) ){

                return $route;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function getMethodsForUri(Request $request): array
    {
        $methods = [];

        foreach( $this->routes as $route ) {
            if( $route->matchHostname($request->getHost()) &&
                $route->matchUri($request->getPathInfo()) ){
                $methods = array_merge($methods, $route->getMethods());
            }
        }

        return array_unique($methods);
    }
}