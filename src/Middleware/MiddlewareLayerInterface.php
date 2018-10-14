<?php

namespace Limber\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface MiddlewareLayerInterface
{
    /**
     * Handle the Middleware layer
     *
     * @param Request $request
     * @param \Closure $next
     * @return Response
     */
    public function handle($request, \Closure $next);
}