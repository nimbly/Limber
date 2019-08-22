<?php

namespace Limber\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
     * @param ServerRequestInterface $request
     * @param callable $kernel
     * @return ResponseInterface
     */
    public function run(ServerRequestInterface $request, callable $kernel): ResponseInterface
    {
        $next = \array_reduce(\array_reverse($this->middlewareStack), function(callable $next, MiddlewareLayerInterface $layer): \Closure {

            return function(ServerRequestInterface $request) use ($next, $layer): ResponseInterface {
                return $layer->handle($request, $next);
            };

        }, function(ServerRequestInterface $request) use ($kernel): ResponseInterface {
            return $kernel($request);
        });

        return $next($request);
    }
}