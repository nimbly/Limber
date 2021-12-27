<?php

namespace Limber\Exceptions;

use Throwable;

/**
 * 400 Bad Request exception.
 */
class BadRequestHttpException extends HttpException
{
	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			400,
			$message ?? "Bad Request",
			[],
			$code,
			$previous
		);
	}
}