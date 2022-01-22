<?php

namespace Nimbly\Limber\Exceptions;

use Throwable;

/**
 * 403 Forbidden exception.
 */
class ForbiddenHttpException extends HttpException
{
	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			403,
			$message ?? "Forbidden",
			[],
			$code,
			$previous
		);
	}
}