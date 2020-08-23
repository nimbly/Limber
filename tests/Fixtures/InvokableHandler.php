<?php

namespace Limber\Tests\Fixtures;

use Capsule\Response;
use Capsule\ResponseStatus;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class InvokableHandler
{
	public function __invoke(ServerRequestInterface $instance): ResponseInterface
	{
		return new Response(
			ResponseStatus::OK,
			"Ok"
		);
	}
}