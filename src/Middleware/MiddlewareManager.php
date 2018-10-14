<?php

namespace Limber\Middleware;


class MiddlewareManager
{
    /**
     * Middleware stack to execute
     *
     * @var MiddlewareLayerInterface[]
     */
    protected $middlewareStack = [];

    /**
     * Manager constructor.
     * @param array $layers
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
    public function push(MiddlewareLayerInterface $layer)
    {
        $this->middlewareStack[] = $layer;
    }

    /**
     * Remove a layer by its classname.
     * 
     * @param $middlewareClass
     */
    public function remove($middlewareClass)
    {
        foreach( $this->middlewareStack as $i => $middleware ){
            if( $middleware instanceof $middlewareClass ){
                unset($this->middlewareStack[$i]);
            }
        }
    }

    /**
     * @param $object
     * @param callable $kernel
     * @return mixed
     */
    public function run($object, callable $kernel)
    {
        $next = array_reduce(array_reverse($this->middlewareStack), function(\Closure $next, MiddlewareLayerInterface $layer) {

            return function($object) use ($next, $layer){
                return $layer->handle($object, $next);
            };

        }, function($object) use ($kernel) {
            return $kernel($object);
        });

        return $next($object);
    }
}