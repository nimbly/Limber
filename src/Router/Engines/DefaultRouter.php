<?php

namespace Nimbly\Limber\Router\Engines;

use Nimbly\Limber\Router\Route;
use Nimbly\Limber\Router\RouterInterface;
use Psr\Http\Message\ServerRequestInterface;

class DefaultRouter implements RouterInterface
{
	/**
	 * Set of indexed methods to routes.
	 *
	 * @var array<string,array<Route>>
	 */
	protected array $routes = [];

	/**
	 * @param array<Route> $routes
	 */
	public function __construct(array $routes = [])
	{
		$this->load($routes);
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
			$this->routes[$method][] = $route;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function load(array $routes): void
	{
		foreach( $routes as $route ){
			$this->indexRoute($route);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getRoutes(): array
	{
		$routes = [];
		foreach( $this->routes as $indexedRoutes ){
			$routes = \array_merge($routes, $indexedRoutes);
		}

		return $routes;
	}

	/**
	 * @inheritDoc
	 */
	public function add(array $methods, string $path, string|callable $handler, array $config = []): Route
	{
		// Create new Route instance
		$route = new Route($methods, $path, $handler, $config);

		// Index the route
		$this->indexRoute($route);

		return $route;
	}

	/**
	 * @inheritDoc
	 */
	public function resolve(ServerRequestInterface $request): ?Route
	{
		foreach( $this->routes[\strtoupper($request->getMethod())] ?? [] as $route ){

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

		foreach( $this->routes as $routes ) {

			foreach( $routes as $route ){
				if( $route->matchPath($request->getUri()->getPath()) &&
					$route->matchHostname($request->getUri()->getHost()) &&
					$route->matchScheme($request->getUri()->getScheme()) ){
					$methods = \array_merge($methods, $route->getMethods());
				}
			}

		}

		return \array_unique($methods);
	}
}