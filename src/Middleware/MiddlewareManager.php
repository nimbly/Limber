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
     * @param array<string|MiddlewareLayerInterface|callable> $layers
     */
    public function __construct(array $layers = [])
    {
        foreach( $layers as $layer ){

			if( $layer instanceof MiddlewareLayerInterface ){
                $this->add($layer);
			}
			elseif( \is_callable($layer) ){
				$this->add(
					new CallableMiddlewareLayer($layer)
				);
			}
            else {

				/**
				 * @psalm-suppress InvalidStringClass
				 * @psalm-suppress ArgumentTypeCoercion
				 */
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
	 * Get the middleware stack.
	 *
	 * @return array<MiddlewareLayerInterface>
	 */
	public function getMiddleware(): array
	{
		return $this->middlewareStack;
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
        $next = \array_reduce(\array_reverse($this->getMiddleware()), function(callable $next, MiddlewareLayerInterface $layer): \closure {

            return function(ServerRequestInterface $request) use ($next, $layer): ResponseInterface {
                return $layer->handle($request, $next);
            };

        }, function(ServerRequestInterface $request) use ($kernel): ResponseInterface {
            return $kernel($request);
        });

        return $next($request);
    }
}