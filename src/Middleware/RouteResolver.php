<?php

namespace Limber\Middleware;

use Limber\Exceptions\MethodNotAllowedHttpException;
use Limber\Exceptions\NotFoundHttpException;
use Limber\MiddlewareManager;
use Limber\RouteManager;
use Limber\Router\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteResolver implements MiddlewareInterface
{
	/**
	 * RouteManager instance.
	 *
	 * @var RouteManager
	 */
	protected $routeManager;

	/**
	 * MiddlewareManager instance.
	 *
	 * @var MiddlewareManager
	 */
	protected $middlewareManager;

	/**
	 * RouteResolver constructor.
	 *
	 * @param RouteManager $routeManager
	 * @param MiddlewareManager $middlewareManager
	 */
	public function __construct(
		RouteManager $routeManager,
		MiddlewareManager $middlewareManager)
	{
		$this->routeManager = $routeManager;
		$this->middlewareManager = $middlewareManager;
	}

	/**
	 * @inheritDoc
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$route = $this->routeManager->resolve($request);

		if( empty($route) ){
			$methods = $this->routeManager->getMethods($request);

			if( $methods ){
				throw new MethodNotAllowedHttpException($methods, "Method not allowed");
			}

			throw new NotFoundHttpException("Route not found");
		}

		// Capture the route attributes (if any) and attach to request instance.
		if( $route->getAttributes() ){
			foreach( $route->getAttributes() as $attribute => $value ){
				$request = $request->withAttribute($attribute, $value);
			}
		}

		// We need to compile the route middleware and splice it into the current chain.
		if( $route->getMiddleware() ){
			$handler = $this->middlewareManager->compile($route->getMiddleware(), $handler);
		}

		return $handler->handle(
			$request->withAttribute(Route::class, $route)
		);
	}
}