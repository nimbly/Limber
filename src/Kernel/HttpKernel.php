<?php

namespace Limber\Kernel;

use Limber\Router\Route;
use Limber\Router\Router;
use Limber\Middleware\MiddlewareManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

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

            // 404 or 405?
            if( ($methods = $this->router->getMethodsForUri($request)) ){
                throw new MethodNotAllowedHttpException($methods);
            }

            throw new NotFoundHttpException();
        }

        return $route;
    }

    /**
     * Kernel invoker
     *
     * @param Request $request
     * @return void
     */
    public function __invoke()
    {
        // Resolve the route
        $route = $this->resolveRoute($this->request);

        // Attach the route to the request
        $this->request->attributes->set(Route::class, $route);

        // Save the path parameters as request attributes
        $this->request->attributes->add($route->getPathParams($request->getUri));

        // Build MiddlewareManager
        $middlewareManager = new MiddlewareManager(array_merge($this->middleware, $route->middleware));

        // Run the middleware stack, making self::dispatch method the core
        $response = $middlewareManager->run($this->request, [$this, 'dispatch']);

        return $response;
    }


    /**
     * Dispatch the request
     *
     * @param Request $request
     * @return Response
     */
    private function dispatch(Request $request)
    {
        /** @var Route */
        $route = $request->attributes->get(Route::class);

        // Callable/closure style route
        if( is_callable($route->action) ){
            $action = $route->action;
        }

        // Class@Method style route
        else {
            $action = class_method($route->action);
        }

        return \call_user_func_array($action, $this->resolveDispatchParameters($requst, $action));
    }

    /**
     * Resolve the dispatch parameters
     *
     * @param [type] $request
     * @param [type] $target
     * @return void
     */
    private function resolveDispatchParameters($request, $target)
    {
        // Get the target's parameters
        if( is_callable($target) ){
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
                $parameter->getType() === Request::class ){
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