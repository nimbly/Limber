<?php

namespace Limber\Exceptions;

use Throwable;

/**
 * 409 Conflict exception.
 */
class ConflictHttpException extends HttpException
{
	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			409,
			$message ?? "Conflict",
			[],
			$code,
			$previous
		);
	}
}