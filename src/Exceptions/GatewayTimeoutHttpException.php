<?php

namespace Nimbly\Limber\Exceptions;

use Throwable;

/**
 * 504 Gateway Timeout exception.
 */
class GatewayTimeoutHttpException extends HttpException
{
	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			504,
			$message ?? "Gateway Timeout",
			[],
			$code,
			$previous
		);
	}
}