<?php

namespace Nimbly\Limber\Tests\Fixtures;

use Capsule\Response;
use Capsule\ResponseStatus;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HandlerClass
{
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		return new Response(
			ResponseStatus::OK,
			"Hello world!"
		);
	}
}