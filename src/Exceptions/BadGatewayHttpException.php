<?php

namespace Nimbly\Limber\Exceptions;

use Throwable;

/**
 * 502 Bad Gateway exception.
 */
class BadGatewayHttpException extends HttpException
{
	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			502,
			$message ?? "Bad Gateway",
			[],
			$code,
			$previous
		);
	}
}