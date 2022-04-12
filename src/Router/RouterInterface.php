<?php

namespace Nimbly\Limber\Router;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

interface RouterInterface
{
	/**
	 * Add a new route.
	 *
	 * @param array<string> $methods HTTP methods route will respond to (get, post, put, etc)
	 * @param string $path Path or endpoint the route responds to.
	 * @param string|callable $handler Request handler for route. Can be a callable or a string in the form "Fully\Qualified\Namespace\ClassName@methodName".
	 * @param array<string|MiddlewareInterface> $middleware Array of middleware to be applied to route.
	 * @param string|null $scheme HTTP scheme route responds to (http or https or null for any).
	 * @param array<string> $hostnames Hostnames route responds to (or empty for any hostname).
	 * @param array<string,mixed> $attributes Key/value pair attributes for route that will be passed to ServerRequestInterface instance.
	 * @return Route
	 */
	public function add(
		array $methods,
		string $path,
		string|callable $handler,
		array $middleware = [],
		?string $scheme = null,
		array $hostnames = [],
		array $attributes = [],
	): Route;

	/**
	 * Match a ServerReqeuestInterface instance to a Route. Returns null of no route
	 * matched request.
	 *
	 * @param ServerRequestInterface $request
	 * @return Route|null
	 */
	public function resolve(ServerRequestInterface $request): ?Route;

	/**
	 * Given a ServerRequestInterface instance, return which methods that endpoint accepts.
	 *
	 * @param ServerRequestInterface $request
	 * @return array<string>
	 */
	public function getSupportedMethods(ServerRequestInterface $request): array;
}