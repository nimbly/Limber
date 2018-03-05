<?php

namespace Limber\Kernel;

use Limber\Router\Route;
use Limber\Router\Router;
use Limber\Middleware\Middleware;
use Limber\Exception\NotFoundHttpException;
use Limber\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HttpKernel extends Kernel
{
    /** @var Request */
    protected $request;

    /** @var Router */
    protected $router;

    /** @var array */
    protected $middleware = [];

    /**
     * @param Request $request
     * @param Router $router
     */
    public function __construct(Request $request, Router $router)
    {
        $this->request = $request;
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
    private function resolveRoute(Request $request)
    {
        if( ($route = $this->router->resolve($request)) === false ){

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
     * @return mixed
     */
    public function run()
    {
        // Resolve the route
        $route = $this->resolveRoute($this->request);

        // Attach the route to the request
        $this->request->attributes->set(Route::class, $route);

        // Save the path parameters as request attributes
        $this->request->attributes->add($route->getPathParams($this->request->getPathInfo()));

        // Build MiddlewareManager
        $middlewareManager = new Middleware(array_merge($this->middleware, $route->middleware));

        // Run the middleware stack, making self::dispatch method the core
        $response = $middlewareManager->run($this->request, [$this, 'dispatch']);

        return $response;
    }

    /**
     * Dispatch a request
     *
     * @param Request $request
     * @return mixed
     */
    public function dispatch(Request $request)
    {
        /** @var Route */
        $route = $request->attributes->get(Route::class);

        // Callable/closure style route
        if( is_callable($route->action) ){
            $action = $route->action;
        }

        // Class@Method style route
        else {
            $action = $this->resolveClassMethod($route->action);
        }

        $params = $this->resolveActionParameters($request, $action);

        return \call_user_func_array($action, $params);
    }

    /**
     * Resolve a Class@Method string
     *
     * @param string $classMethod
     * @return callable
     */
    private function resolveClassMethod($classMethod)
    {
        if( preg_match('/^([\\\d\w_]+)@([\d\w_]+)$/', $classMethod, $match) ){

            if( class_exists($match[1]) ){

                $instance = new $match[1];

                if( \method_exists($instance, $match[2]) ){
                    return [$instance, $match[2]];
                }
            }
        }

        throw new \ErrorException("Cannot resolve class method: {$classMethod}");
    }

    /**
     * Resolve the dispatch parameters
     *
     * @param Request $request
     * @param callable $target
     * @return void
     */
    private function resolveActionParameters($request, $target)
    {
        // Get the target's parameters
        if( $target instanceof \Closure ){
            $functionParameters = (new \ReflectionFunction($target))->getParameters();
        }

        else {
            $functionParameters = (new \ReflectionClass(get_class($target[0])))->getMethod($target[1])->getParameters();
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