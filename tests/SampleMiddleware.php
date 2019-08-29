<?php

namespace Limber\Tests;

use Limber\Middleware\MiddlewareLayerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SampleMiddleware implements MiddlewareLayerInterface
{
	public function handle(ServerRequestInterface $request, callable $next): ResponseInterface
	{
		$request = $request->withAddedHeader("X-Sample-Middleware", "Limber");

		$response = $next($request);

		$response = $response->withAddedHeader("X-Sample-Middleware", "Limber");

		return $response;
	}
}