<?php

namespace Nimbly\Limber\Router\Engines;

use Nimbly\Limber\Router\Route;

class RouteBranch
{
	/**
	 * Full path.
	 *
	 * @var string|null
	 */
	protected ?string $path;

	/**
	 * Routes indexed by HTTP method.
	 *
	 * @var array<string,Route>
	 */
	protected array $routes = [];

	/**
	 * Further branches dangling from this branch.
	 *
	 * @var array<string,RouteBranch>
	 */
	protected array $branches = [];

	/**
	 * RouteBranch constructor.
	 *
	 * @param string|null $path
	 */
	public function __construct(?string $path = null)
	{
		$this->path = $path;
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
	 * @param string $pathPart
	 * @return RouteBranch
	 */
	public function next(string $pathPart): RouteBranch
	{
		foreach( $this->branches as $key => $branch ){

			if( $pathPart === $key ){
				return $branch;
			}
		}

		return $this->addBranch($pathPart);
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