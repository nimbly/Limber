<?php

namespace Limber\Middleware;


interface MiddlewareInterface
{
    public function handle($request, \Closure $next);
}