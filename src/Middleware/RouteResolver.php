<?php

namespace Nimbly\Limber\Middleware;

use Nimbly\Limber\Exceptions\MethodNotAllowedHttpException;
use Nimbly\Limber\Exceptions\NotFoundHttpException;
use Nimbly\Limber\MiddlewareManager;
use Nimbly\Limber\Router\Route;
use Nimbly\Limber\Router\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteResolver implements MiddlewareInterface
{
	public function __construct(
		protected Router $router,
		protected MiddlewareManager $middlewareManager)
	{
	}

	/**
	 * @inheritDoc
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$route = $this->router->resolve($request);

		if( empty($route) ){
			$methods = $this->router->getSupportedMethods($request);

			// 404 Not Found
			if( empty($methods) ){
				throw new NotFoundHttpException("Route not found");
			}

			// 405 Method Not Allowed
			throw new MethodNotAllowedHttpException($methods);
		}

		// Attach route attributes to RequestInterface instance
		foreach( $route->getAttributes() as $attribute => $value ){
			$request = $request->withAttribute($attribute, $value);
		}

		// If route has specific middleware, apply those.
		if( $route->getMiddleware() ){
			$handler = $this->middlewareManager->compile(
				$this->middlewareManager->normalize(\array_reverse($route->getMiddleware(), true)),
				$handler
			);
		}

		return $handler->handle($request->withAttribute(Route::class, $route));
	}
}