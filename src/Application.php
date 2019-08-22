<?php

namespace Limber;

use Limber\Exceptions\DispatchException;
use Limber\Exceptions\MethodNotAllowedHttpException;
use Limber\Exceptions\NotFoundHttpException;
use Limber\Middleware\MiddlewareLayerInterface;
use Limber\Middleware\MiddlewareManager;
use Limber\Router\Route;
use Limber\Router\RouterAbstract;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Application
{
    /**
     * Router instance.
     *
     * @var RouterAbstract
     */
    protected $router;

    /**
     * Global middleware.
     *
     * @var array
     */
    protected $middleware = [];

    /**
     *
     * Limber Framework Application constructor.
     *
     * @param RouterAbstract $router
     */
    public function __construct(RouterAbstract $router)
    {
        $this->router = $router;
    }

    /**
     * Set the global middleware to run.
     *
     * @param array<string|MiddlewareLayerInterface|callable> $middleware
     * @return void
     */
    public function setMiddleware(array $middleware): void
    {
        $this->middleware = $middleware;
	}

	/**
	 * Add a middleware layer.
	 *
	 * @param string|MiddlewareLayerInterface|callable $middleware
	 * @return void
	 */
	public function addMiddleware($middleware): void
	{
		$this->middleware[] = $middleware;
	}

    /**
     * Dispatch a request.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        // Resolve the route
        $route = $this->resolveRoute($request);

        // Build MiddlewareManager
        $middlewareManager = new MiddlewareManager(
            \array_merge($this->middleware, $route->getMiddleware())
        );

        // Run the middleware stack
        return $middlewareManager->run($request, function(ServerRequestInterface $request) use ($route): ResponseInterface {

            // Callable/closure style route
            if( \is_callable($route->getAction()) ){
                $action = $route->getAction();
            }

            // Class@Method style route
            elseif( \is_string($route->getAction()) ) {
                $action = \class_method($route->getAction());
            }
            else {
                throw new DispatchException("Cannot dispatch route because target cannot be resolved.");
            }

            return \call_user_func_array($action, \array_merge(
                [$request],
                \array_values($route->getPathParams($request->getUri()->getPath()))
            ));

        });
    }

    /**
     * Resolve to a Route instance or throw exception
     *
     * @param ServerRequestInterface $request
     * @throws NotFoundHttpException
     * @throws MethodNotAllowedHttpException
     * @return Route
     */
    private function resolveRoute(ServerRequestInterface $request): Route
    {
        if( ($route = $this->router->resolve($request)) === null ){

            // 405 Method Not Allowed
            if( ($methods = $this->router->getMethodsForUri($request)) ){
                throw new MethodNotAllowedHttpException;
            }

            // 404 Not Found
            throw new NotFoundHttpException("Route not found");
        }

        return $route;
    }

    /**
     * Send a response back to calling client.
     *
     * @param ResponseInterface $response
     * @return void
     */
    public function send(ResponseInterface $response): void
    {
        if( !\headers_sent() ){
            \header(
                \sprintf("HTTP/%s %s %s", $response->getProtocolVersion(), $response->getStatusCode(), $response->getReasonPhrase())
            );

            foreach( $response->getHeaders() as $header => $values ){
                foreach( $values as $value ){
                    \header(
                        \sprintf("%s: %s", $header, $value)
                    );
                }
            }
        }

        echo $response->getBody()->getContents();
    }
}