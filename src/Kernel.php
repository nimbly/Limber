<?php

namespace Limber;

use Limber\Exceptions\DispatchException;
use Limber\Router\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Kernel implements RequestHandlerInterface
{
	/**
	 * Handle the ServerRequestInteface by passing it off to the Kernel handler.
	 *
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		/** @var Route|null $route */
		$route = $request->getAttribute(Route::class);

		if( empty($route) ){
			throw new DispatchException("Route attribute not found on ServerRequest instance.");
		}

		return \call_user_func_array(
			$route->getCallableAction(),
			\array_merge(
				[$request],
				\array_values(
					$route->getPathParams($request->getUri()->getPath())
				)
			)
		);
	}
}