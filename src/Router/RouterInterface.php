<?php

namespace Nimbly\Limber\Router;

use Psr\Http\Message\ServerRequestInterface;

interface RouterInterface
{
	/**
	 * Load routes into engine.
	 *
	 * @param array<Route> $routes
	 * @return void
	 */
	public function load(array $routes): void;

	/**
	 * Resolve a ServerRequestInterface instance into a matching route.
	 *
	 * @param ServerRequestInterface $request
	 * @return Route|null
	 */
	public function resolve(ServerRequestInterface $request): ?Route;

	/**
	 * Add a route.
	 *
	 * @param array<string> $methods Allowed HTTP methods for route.
	 * @param string $path
	 * @param string|callable $handler
	 * @param array<string,mixed> $config
	 * @return Route
	 */
	public function add(array $methods, string $path, string|callable $handler, array $config = []): Route;

	/**
	 * Return all HTTP methods supported by the endpoint.
	 *
	 * @param ServerRequestInterface $request
	 * @return array<string>
	 */
	public function getMethods(ServerRequestInterface $request): array;

	/**
	 * Get all routes registered.
	 *
	 * @return array<Route>
	 */
	public function getRoutes(): array;
}