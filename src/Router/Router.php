<?php

namespace Limber\Router;

use Psr\Http\Message\ServerRequestInterface;

class Router extends RouterAbstract
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
     * @inheritDoc
     */
    protected function indexRoute(Route $route): void
    {
        foreach( $route->getMethods() as $method ){
            $this->indexes[$method][] = $route;
        }
    }

    /**
     * @inheritDoc
     */
    public function add(array $methods, string $uri, $target): Route
    {
        // Create new Route instance
        $route = new Route($methods, $uri, $target, $this->config);

        // Index the route
        $this->indexRoute($route);

        return $route;
    }

    /**
     * @inheritDoc
     */
    public function resolve(ServerRequestInterface $request): ?Route
    {
        foreach( $this->indexes[\strtoupper($request->getMethod())] ?? [] as $route ){

            if( $route->matchUri($request->getUri()->getPath() ?? "") &&
                $route->matchMethod($request->getMethod() ?? "") &&
                $route->matchHostname($request->getUri()->getHost() ?? "") &&
                $route->matchScheme($request->getUri()->getScheme() ?? "") ){

                return $route;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function getMethodsForUri(ServerRequestInterface $request): array
    {
        $methods = [];

        foreach( $this->indexes as $routes ) {

            foreach( $routes as $route ){
                if( $route->matchUri($request->getUri()->getPath()) &&
                    $route->matchHostname($request->getUri()->getHost()) &&
                    $route->matchScheme($request->getUri()->getScheme()) ){
                    $methods = \array_merge($methods, $route->getMethods());
                }
            }

        }

        return \array_unique($methods);
    }
}