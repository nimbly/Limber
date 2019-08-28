<?php

namespace Limber\Router\Engines;

use Limber\Router\Route;
use Limber\Router\RouterInterface;
use Psr\Http\Message\ServerRequestInterface;

class TreeRouter implements RouterInterface
{
    /**
     * Set of indexed RouteBranches
     *
     * @var RouteBranch
     */
    protected $root;

    /**
     * TreeRouter constructor.
	 *
	 * @param array<Route> $routes
     */
    public function __construct(array $routes = [])
    {
		$this->root = new RouteBranch;
		$this->load($routes);
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
		return $this->getRoutesFromBranch($this->root);
	}

	/**
	 * Recursive method to traverse tree from given branch and flatten out routes.
	 *
	 * @param RouteBranch $branch
	 * @return array
	 */
	private function getRoutesFromBranch(RouteBranch $branch): array
	{
		$routes = \array_values($branch->getRoutes());

		foreach( $branch->getBranches() as $branch ){
			$routes = \array_merge($routes, $this->getRoutesFromBranch($branch));
		}

		return $routes;
	}

    /**
     * Index the route.
     *
     * @param Route $route
     * @return void
     */
    protected function indexRoute(Route $route): void
    {
        $currentBranch = $this->root;
        $patternParts = $route->getPatternParts();

        foreach( $patternParts as $pattern ){
            $currentBranch = $currentBranch->next($pattern);
        }

        $currentBranch->addRoute($route);
    }

    /**
     * @inheritDoc
     */
    public function add(array $methods, string $uri, $target, array $config = []): Route
    {
        // Create new Route instance
        $route = new Route($methods, $uri, $target, $config);

        // Index the route
        $this->indexRoute($route);

        return $route;
    }

    /**
     * @inheritDoc
     */
    public function resolve(ServerRequestInterface $request): ?Route
    {
        // Break the request path apart
        $pathParts = \explode("/", \trim($request->getUri()->getPath(), "/"));

        // Set the starting node.
        $branch = $this->root;

        foreach( $pathParts as $part ){
            if( ($branch = $branch->findBranch($part)) === null ){
                return null;
            }
		}

		$route = $branch->getRouteForMethod($request->getMethod());

		if( empty($route) ){
			return null;
		}

        // Now match against the remaining criteria.
        if( $route->matchScheme($request->getUri()->getScheme()) &&
            $route->matchHostname($request->getUri()->getHost()) ){

            return $route;
		}

		return null;
    }

    /**
     * @inheritDoc
     */
    public function getMethods(ServerRequestInterface $request): array
    {
        // Break the request path apart
        $pathParts = \explode("/", \trim($request->getUri()->getPath(), "/"));

        // Set the starting node.
        $branch = $this->root;

        foreach( $pathParts as $part ){
            if( ($branch = $branch->findBranch($part)) === null ){
                return [];
            }
        }

        return $branch->getMethods();
    }
}