<?php

namespace Limber\Router;

use Limber\Exceptions\MethodNotAllowedHttpException;
use Limber\Exceptions\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;

class TreeRouter extends RouterAbstract
{
    /**
     * Set of indexed RouteBranches
     *
     * @var RouteBranch
     */
    protected $tree;

    /**
     * @inheritDoc
     */
    public function __construct(array $routes = null)
    {
        $this->tree = new RouteBranch;

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
        $currentBranch = $this->tree;
        $patternParts = $route->getPatternParts();

        foreach( $patternParts as $pattern ){
            $currentBranch = $currentBranch->next($pattern);
        }

        $currentBranch->addRoute($route);
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function resolve(Request $request): ?Route
    {
        // Break the request path apart
        $pathParts = explode("/", trim($request->getPathInfo(), "/"));

        // Set the starting node.
        $branch = $this->tree;

        foreach( $pathParts as $part ){
            if( ($branch = $branch->findBranch($part)) === null ){
                throw new NotFoundHttpException("Route not found.");
            }
        }

        if( ($route = $branch->getRouteForMethod($request->getMethod())) === null ){
            throw new MethodNotAllowedHttpException;
        }

        // Now match against the remaining criteria.
        if( $route->matchScheme($request->getScheme()) &&
            $route->matchHostname($request->getHost()) ){

            return $route;
        }

        throw new NotFoundHttpException("Route not found.");
    }

    /**
     * @inheritDoc
     */
    public function getMethodsForUri(Request $request): array
    {
        // Break the request path apart
        $pathParts = explode("/", trim($request->getPathInfo(), "/"));

        // Set the starting node.
        $branch = $this->tree;

        foreach( $pathParts as $part ){
            if( ($branch = $branch->findBranch($part)) === null ){
                return [];
            }
        }

        return $branch->getMethods();
    }
}