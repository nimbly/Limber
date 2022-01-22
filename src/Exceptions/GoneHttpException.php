<?php

namespace Nimbly\Limber\Exceptions;

use Throwable;

/**
 * 410 Gone exception.
 */
class GoneHttpException extends HttpException
{
	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			410,
			$message ?? "Gone",
			[],
			$code,
			$previous
		);
	}
}