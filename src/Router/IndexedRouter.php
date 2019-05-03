<?php

namespace Limber\Router;

use Symfony\Component\HttpFoundation\Request;

class IndexedRouter extends Router
{
    /**
     * Set of indexed pointers to routes.
     *
     * @var array<string, array<Route>>
     */
    protected $indexes = [];

    /**
     * Router constructor.
     * @param array<Route>|null $routes
     */
    public function __construct(array $routes = null)
    {
        if( $routes ){
            foreach( $routes as $route ){
                $this->indexRoute($route);
            }
        }
    }

    /**
     * Index the route.
     *
     * @param Route $route
     * @return void
     */
    protected function indexRoute(Route $route): void
    {
        foreach( $route->getMethods() as $method ){
            $this->indexes[$method][] = $route;
        }
    }

    /**
     * Add a route
     *
     * @param array $methods
     * @param string $uri
     * @param string|callable $target
     * @return Route
     */
    public function add($methods, string $uri, $target): Route
    {
        // Create new Route instance
        $route = new Route($methods, $uri, $target, $this->config);

        // Index the route
        $this->indexRoute($route);

        return $route;
    }

    /**
     * Resolve a request into its route.
     *
     * @param Request $request
     * @return Route|null
     */
    public function resolve(Request $request): ?Route
    {
        foreach( $this->indexes[$request->getMethod()] as $route ){

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

        foreach( $this->indexes as $routes ) {

            foreach( $routes as $route ){
                if( $route->matchUri($request->getPathInfo()) &&
                    $route->matchHostname($request->getHost()) &&
                    $route->matchScheme($request->getScheme()) ){
                    $methods = array_merge($methods, $route->getMethods());
                }
            }
            
        }

        return array_unique($methods);
    }
}