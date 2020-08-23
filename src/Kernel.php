<?php

namespace Limber;

use Limber\Exceptions\ApplicationException;
use Limber\Exceptions\HandlerException;
use Limber\Router\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Kernel implements RequestHandlerInterface
{
	/**
	 * DependencyManager interface.
	 *
	 * @var DependencyManager
	 */
	protected $dependencyManager;

	/**
	 * Kernel constructor.
	 *
	 * @param DependencyManager $dependencyManager
	 */
	public function __construct(DependencyManager $dependencyManager)
	{
		$this->dependencyManager = $dependencyManager;
	}

	/**
	 * Pass request off to Route handler.
	 *
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		/**
		 * @var Route|null $route
		 */
		$route = $request->getAttribute(Route::class);

		if( empty($route) ){
			throw new ApplicationException("Route instance not found in ServerRequest instance attributes.");
		}

		return $this->dependencyManager->call(
			$this->getCallableHandler($route->getHandler()),
			\array_merge(
				[ServerRequestInterface::class => $request],
				$route->getPathParams($request->getUri()->getPath())
			)
		);
	}

	/**
	 * Convert the Route handler into something callable.
	 *
	 * @param mixed $handler
	 * @return callable
	 */
	private function getCallableHandler($handler): callable
	{
		if( \is_string($handler) ){
			if( \preg_match("/^(.+)@(.+)$/", $handler, $match) ){
				$handler = [
					$this->dependencyManager->make($match[1]),
					$match[2]
				];
			}
			elseif( \class_exists($handler) ) {
				$handler = $this->dependencyManager->make($handler);
			}
		}

		if( \is_callable($handler) === false ){
			throw new HandlerException("Route handler is not callable.");
		}

		return $handler;
	}
}