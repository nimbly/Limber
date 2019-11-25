<?php

namespace Limber\Middleware;

use Limber\Exceptions\MethodNotAllowedHttpException;
use Limber\Exceptions\NotFoundHttpException;
use Limber\MiddlewareManager;
use Limber\Router\Route;
use Limber\Router\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteResolverMiddleware implements MiddlewareInterface
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

	public function __construct(Router $router, MiddlewareManager $middlewareManager)
	{
		$this->router = $router;
		$this->middlewareManager = $middlewareManager;
	}

	/**
	 * Resolve the ServerRequestInterface instance to a Route.
	 *
	 * Modify the middleware chain to use the Route middleware.
	 *
	 * @param ServerRequestInterface $request
	 * @param RequestHandlerInterface $handler
	 * @throws MethodNotAllowedHttpException
	 * @throws NotFoundHttpException
	 * @return ResponseInterface
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$route = $this->router->resolve($request);

		if( empty($route) ){

			$allowedMethods = $this->router->getMethods($request);

			// 405 Method Not Allowed
			if( $allowedMethods ){
				throw new MethodNotAllowedHttpException($allowedMethods);
			}

			// 404 Not Found
			throw new NotFoundHttpException("Route not found");
		}

		// Insert the Route middleware into the chain.
		$handler = $this->middlewareManager->compile(
			$route->getMiddleware(),
			$handler
		);

		// Assign the Route to a ServerRequest attribute.
		return $handler->handle(
			$request->withAttribute(Route::class, $route)
		);
	}
}