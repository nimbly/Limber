<?php

namespace Nimbly\Limber\Exceptions;

use Throwable;

/**
 * 500 Internal Server Error exception
 */
class InternalServerErrorHttpException extends HttpException
{
	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			500,
			$message ?? "Internal Server Error",
			[],
			$code,
			$previous
		);
	}
}