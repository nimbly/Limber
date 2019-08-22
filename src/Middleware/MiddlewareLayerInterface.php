<?php

namespace Limber\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface MiddlewareLayerInterface
{
    /**
     * Handle the Middleware layer.
     *
     * @param ServerRequestInterface $request
     * @param callable $next
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request, callable $next): ResponseInterface;
}