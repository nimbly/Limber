<?php

namespace Limber\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MiddlewareManager
{
    /**
     * Middleware stack to execute
     *
     * @var array<MiddlewareLayerInterface>
     */
    protected $middlewareStack = [];

    /**
     * MiddlewareManager constructor.
     * 
     * @param array<string>|array<MiddlewareLayerInterface> $layers
     */
    public function __construct(array $layers = [])
    {
        foreach( $layers as $layer ){

            if( $layer instanceof MiddlewareLayerInterface ){
                $this->add($layer);
            }

            else {
                $this->add(new $layer);
            }
        }
    }

    /**
     * Add a layer to the stack.
     * 
     * @param MiddlewareLayerInterface $layer
     */
    public function add(MiddlewareLayerInterface $layer): void
    {
        $this->middlewareStack[] = $layer;
    }

    /**
     * Remove a layer by its classname.
     * 
     * @param string $middlewareClass
     */
    public function remove(string $middlewareClass): void
    {
        foreach( $this->middlewareStack as $i => $middleware ){
            if( $middleware instanceof $middlewareClass ){
                unset($this->middlewareStack[$i]);
            }
        }
    }

    /**
     * Run the middleware stack.
     * 
     * @param Request $request
     * @param callable $kernel
     * @return Response
     */
    public function run(Request $request, callable $kernel): Response
    {
        $next = array_reduce(array_reverse($this->middlewareStack), function(callable $next, MiddlewareLayerInterface $layer): \Closure {

            return function(Request $request) use ($next, $layer): Response {
                return $layer->handle($request, $next);
            };

        }, function(Request $request) use ($kernel): Response {
            return $kernel($request);
        });

        return $next($request);
    }
}