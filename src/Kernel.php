<?php

namespace Limber;

use Limber\Exceptions\MethodNotAllowedHttpException;
use Limber\Exceptions\NotFoundHttpException;
use Limber\Router\Route;
use Limber\Router\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Kernel implements RequestHandlerInterface
{
	/**
	 * Router instance.
	 *
	 * @var Router
	 */
	protected $router;

	/**
	 * Route instance.
	 *
	 * @var Route|null
	 */
	protected $route;

	/**
	 * Kernel constructor.
	 *
	 * @param Router $router
	 * @param Route|null $route
	 */
	public function __construct(Router $router, ?Route $route)
	{
		$this->router = $router;
		$this->route = $route;
	}

	/**
	 * Handle the ServerRequestInteface by passing it off to the Kernel handler.
	 *
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		if( empty($this->route) ){

			$allowedMethods = $this->router->getMethods($request);

			// 405 Method Not Allowed
			if( $allowedMethods ){
				throw new MethodNotAllowedHttpException($allowedMethods);
			}

			// 404 Not Found
			throw new NotFoundHttpException("Route not found");
		}

		return \call_user_func_array(
			$this->route->getCallableAction(),
			\array_merge(
				[$request],
				\array_values(
					$this->route->getPathParams($request->getUri()->getPath())
				)
			)
		);
	}
}