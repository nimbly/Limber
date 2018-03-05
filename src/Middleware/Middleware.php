<?php

namespace Limber\Middleware;


class Middleware
{
    /** @var Layer[] */
    protected $middlewareStack = [];

    /**
     * Manager constructor.
     * @param array $layers
     */
    public function __construct(array $layers = [])
    {
        foreach( $layers as $layer ){

            if( $layer instanceof Layer ){
                $this->add($layer);
            }

            else {
                $this->add(new $layer);
            }
        }
    }

    /**
     * @param Layer $layer
     */
    public function add(Layer $layer)
    {
        $this->middlewareStack[] = $layer;
    }

    /**
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
        $next = array_reduce(array_reverse($this->middlewareStack), function(\Closure $next, Layer $layer) {

            return function($object) use ($next, $layer){
                return $layer->handle($object, $next);
            };

        }, function($object) use ($kernel) {
            return $kernel($object);
        });

        return $next($object);
    }
}