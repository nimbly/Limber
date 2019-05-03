<?php

namespace Limber\Kernel;

use Limber\Exceptions\MethodNotAllowedHttpException;
use Limber\Exceptions\NotFoundHttpException;
use Limber\Middleware\MiddlewareManager;
use Limber\Router\Route;
use Limber\Router\RouterAbstract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HttpKernel
{
    /**
     * Router instance.
     *
     * @var RouterAbstract
     */
    protected $router;

    /**
     * Global middleware to execute on each HTTP request.
     *
     * @var array<string>
     */
    protected $middleware = [];

    /**
     * HttpKernel constructor
     * 
     * @param RouterAbstract $router
     */
    public function __construct(RouterAbstract $router)
    {
        $this->router = $router;
    }
    
    /**
     * Resolve to a Route instance or throw exception
     *
     * @param Request $request
     * @throws NotFoundHttpException
     * @throws MethodNotAllowedHttpException
     * @return Route
     */
    private function resolveRoute(Request $request): Route
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
     * Kernel invoker
     *
     * @param Request $request
     * @return Response
     */
    public function run(Request $request): Response
    {
        // Resolve the route
        $route = $this->resolveRoute($request);

        // Merge the path-based parameters into the request attributes
        $request->attributes->add($route->getPathParams($request->getPathInfo()));

        // Dispatch the request
        return $this->dispatch($request, $route);
    }

    /**
     * Dispatch a request
     *
     * @param Request $request
     * @return Response
     */
    public function dispatch(Request $request, Route $route): Response
    {
        // Build MiddlewareManager
        $middlewareManager = new MiddlewareManager(array_merge($this->middleware, $route->getMiddleware()));

        // Run the middleware stack
        return $middlewareManager->run($request, function(Request $request) use ($route): Response {

            // Callable/closure style route
            if( is_callable($route->getAction()) ){
                $action = $route->getAction();
            }

            // Class@Method style route
            elseif( is_string($route->getAction()) ) {
                $action = class_method($route->getAction());
            }
            else {
                throw new \Exception("Cannot dispatch route because target cannot be resolved.");
            }

            // Auto-resolve controller parameters
            $params = $this->resolveActionParameters($request, $action);

            return \call_user_func_array($action, $params);

        });
    }

    /**
     * Resolve the action parameters for dependency injection.
     *
     * @param Request $request
     * @param callable $target
     * @return array
     */
    private function resolveActionParameters(Request $request, callable $target): array
    {
        // Get the target's parameters
        if( is_array($target) ) {
            $functionParameters = (new \ReflectionClass(get_class($target[0])))->getMethod($target[1])->getParameters();
        }

        else{
            $functionParameters = (new \ReflectionFunction($target))->getParameters();
        }

        $params = [];

        /** @var \ReflectionParameter $parameter */
        foreach( $functionParameters as $parameter ){

            /**
             *
             * @NOTE We cast the ReflectionType to string because the ::getName() method does not appear in
             * PHP 7 until 7.1
             *
             */
            // Check container first for an instance of this parameter type
            if( $parameter->getType() &&
                (string) $parameter->getType() === Request::class ){
                $params[$parameter->getPosition()] = $request;
            }

            // Check request attributes for this parameter name
            elseif( $request->attributes->has($parameter->getName()) ){
                $params[$parameter->getPosition()] = $request->attributes->get($parameter->getName());
            }

            else {
                $params[$parameter->getPosition()] = null;
            }
        }

        return $params;
    }
}