<?php

namespace Nimbly\Limber;

use Nimbly\Resolve\Resolve;
use Nimbly\Limber\Router\Route;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Nimbly\Limber\Exceptions\RouteException;

class Kernel implements RequestHandlerInterface
{
	use Resolve;

	public function __construct(
		protected ?ContainerInterface $container = null
	)
	{
	}

	/**
	 * @inheritDoc
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		/** @var Route|null */
		$route = $request->getAttribute(Route::class);

		if( empty($route) ){
			throw new RouteException("Route request attribute not found.");
		}

		// Make the Route handler callable
		$routeHandler = $this->makeCallable(
			thing: $route->getHandler(),
			container: $this->container,
			parameters: \array_merge(
				[ServerRequestInterface::class => $request],
				$route->getPathParameters($request->getUri()->getPath()),
				$request->getAttributes(),
			)
		);

		// Call the request handler
		return $this->call(
			callable: $routeHandler,
			container: $this->container,
			parameters: \array_merge(
				[ServerRequestInterface::class => $request],
				$route->getPathParameters($request->getUri()->getPath()),
				$request->getAttributes(),
			)
		);
	}
}