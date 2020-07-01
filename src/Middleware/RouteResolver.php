<?php

namespace Limber\Middleware;

use Limber\Exceptions\ApplicationException;
use Limber\Exceptions\MethodNotAllowedHttpException;
use Limber\Exceptions\NotFoundHttpException;
use Limber\MiddlewareManager;
use Limber\Router\Route;
use Limber\Router\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteResolver implements MiddlewareInterface
{
	/**
	 * Router instance.
	 *
	 * @var Router
	 */
	protected $router;

	/**
	 * MiddlewareManager instance.
	 *
	 * @var MiddlewareManager
	 */
	protected $middlewareManager;

	public function __construct(
		Router $router,
		MiddlewareManager $middlewareManager)
	{
		$this->router = $router;
		$this->middlewareManager = $middlewareManager;
	}

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$route = $this->router->resolve($request);

		if( empty($route) ){
			$methods = $this->router->getMethods($request);

			if( $methods ){
				throw new MethodNotAllowedHttpException($methods, "Method not allowed");
			}

			throw new NotFoundHttpException("Route not found");
		}

		// We need to compile this middleware and splice it into this chain.
		if( $route->getMiddleware() ){
			$handler = $this->middlewareManager->compile($route->getMiddleware(), $handler);
		}

		return $handler->handle(
			$request->withAttribute(Route::class, $route)
		);
	}
}