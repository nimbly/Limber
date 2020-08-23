<?php

namespace Limber\Router\Engines;

use Limber\Router\Route;
use Limber\Router\RouterInterface;
use Psr\Http\Message\ServerRequestInterface;

class FlatRouter implements RouterInterface
{
    /**
     * @var array<Route>
     */
    protected $routes = [];

    /**
     * @inheritDoc
     */
    public function __construct(array $routes = [])
    {
        $this->load($routes);
	}

	/**
	 * @inheritDoc
	 */
	public function load(array $routes): void
	{
		$this->routes = $routes;
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
    public function add(array $methods, string $path, $handler, array $config = []): Route
    {
        // Create new Route instance
        $route = new Route($methods, $path, $handler, $config);
        $this->routes[] = $route;
        return $route;
    }

    /**
     * @inheritDoc
     */
    public function resolve(ServerRequestInterface $request): ?Route
    {
        foreach( $this->routes as $route ){

            if( $route->matchPath($request->getUri()->getPath()) &&
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
    public function getMethods(ServerRequestInterface $request): array
    {
        $methods = [];

        foreach( $this->routes as $route ) {
            if( $route->matchHostname($request->getUri()->getHost()) &&
                $route->matchPath($request->getUri()->getPath()) ){
                $methods = \array_merge($methods, $route->getMethods());
            }
        }

        return \array_unique($methods);
    }
}