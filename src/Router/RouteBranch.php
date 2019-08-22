<?php

namespace Limber\Router;

class RouteBranch
{
    /**
     * Full path.
     *
     * @var string|null
     */
    protected $path;

    /**
     * Routes indexed by HTTP method.
     *
     * @var array<string, Route>
     */
    protected $routes = [];

    /**
     * Further branches dangling from this branch.
     *
     * @var array<string, RouteBranch>
     */
    protected $branches = [];

    /**
     * RouteBranch constructor.
     *
     * @param string $path
     */
    public function __construct(string $path = null)
    {
        if( $path ){
            $this->path = $path;
        }
    }

    /**
     * Get the route that responds to this method.
     *
     * @param string $method
     * @return Route|null
     */
    public function getRouteForMethod(string $method): ?Route
    {
        return $this->routes[\strtoupper($method)] ?? null;
    }

    /**
     * Add a branch dangling from this branch.
     *
     * @param string $key
     * @param RouteBranch $routeBranch
     * @return RouteBranch
     */
    protected function addBranch(string $key): RouteBranch
    {
        if( \array_key_exists($key, $this->branches) ){
            throw new \Exception("{$key} branch already exists for this node.");
        }

        $this->branches[$key] = new RouteBranch("{$this->path}/{$key}");
        return $this->branches[$key];
	}

	/**
	 * Get all routes registered on this branch.
	 *
	 * @return array
	 */
	public function getRoutes(): array
	{
		return $this->routes;
	}

    /**
     * Add route to this leaf.
     *
     * @param Route $route
     * @return void
     */
    public function addRoute(Route $route): void
    {
        foreach( $route->getMethods() as $method ){

            if( \array_key_exists($method, $this->routes) ){
                throw new \Exception("{$method}#{$this->path} route has already been defined.");
            }

            $this->routes[$method] = $route;
        }
	}

	/**
	 * Get all the branches on this node.
	 *
	 * @return array
	 */
	public function getBranches(): array
	{
		return $this->branches;
	}

    /**
     * Find the matching branch for the given URI path part.
     *
     * @param string $part
     * @return RouteBranch|null
     */
    public function findBranch(string $part): ?RouteBranch
    {
        // Try finding an exact match first.
        if( \array_key_exists($part, $this->branches) ){
            return $this->branches[$part];
        }

        // Loop through each branch key and match it using a regex.
        foreach( $this->branches as $key => $branch ){
            if( \preg_match("/^{$key}$/", $part) ){
                return $branch;
            }
        }

        return null;
    }

    /**
     * Get the next branch or create a new one.
     *
     * @param string $uriPart
     * @return RouteBranch
     */
    public function next(string $uriPart): RouteBranch
    {
        foreach( $this->branches as $key => $branch ){

            if( $uriPart === $key ){
                return $branch;
            }
        }

        return $this->addBranch($uriPart);
    }

    /**
     * Get all the HTTP methods this branch responsds to.
     *
     * @return array<string>
     */
    public function getMethods(): array
    {
        return \array_keys($this->routes);
    }
}