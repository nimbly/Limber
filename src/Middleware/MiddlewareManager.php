<?php

namespace Limber\Middleware;


class MiddlewareManager
{
    /** @var MiddlewareInterface[] */
    protected $middlewareStack = [];

    /**
     * Manager constructor.
     * @param array $layers
     */
    public function __construct(array $layers = [])
    {
        foreach( $layers as $layer ){

            if( $layer instanceof MiddlewareInterface ){
                $this->add($layer);
            }

            else {
                $this->add(new $layer);
            }
        }
    }

    /**
     * @param MiddlewareInterface $middleware
     */
    public function add(MiddlewareInterface $middleware)
    {
        $this->middlewareStack[] = $middleware;
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
        $next = array_reduce(array_reverse($this->middlewareStack), function(\Closure $next, MiddlewareInterface $layer) {

            return function($object) use ($next, $layer){
                return $layer->handle($object, $next);
            };

        }, function($object) use ($kernel) {
            return $kernel($object);
        });

        return $next($object);
    }
}