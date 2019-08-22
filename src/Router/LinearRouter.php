<?php

namespace Limber\Router;

use Psr\Http\Message\ServerRequestInterface;

class LinearRouter extends RouterAbstract
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
	public function getRoutes(): array
	{
		return $this->routes;
	}

    /**
     * @inheritDoc
     */
    public function add(array $methods, string $uri, $target): Route
    {
        // Create new Route instance
        $route = new Route($methods, $uri, $target, $this->config);
        $this->routes[] = $route;
        return $route;
    }

    /**
     * @inheritDoc
     */
    public function resolve(ServerRequestInterface $request): ?Route
    {
        foreach( $this->routes as $route ){

            if( $route->matchUri($request->getUri()->getPath()) &&
                $route->matchMethod($request->getMethod()) &&
                $route->matchHostname($request->getUri()->getHost()) &&
                $route->matchScheme($request->getUri()->getScheme()) ){

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

        foreach( $this->routes as $route ) {
            if( $route->matchHostname($request->getUri()->getHost()) &&
                $route->matchUri($request->getUri()->getPath()) ){
                $methods = \array_merge($methods, $route->getMethods());
            }
        }

        return \array_unique($methods);
    }
}