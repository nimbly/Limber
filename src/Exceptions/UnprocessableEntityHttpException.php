<?php

namespace Limber\Exceptions;

use Throwable;

/**
 * 422 Unprocessable Entity exception.
 */
class UnprocessableEntityHttpException extends HttpException
{
	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			422,
			$message ?? "Unprocessable Entity",
			[],
			$code,
			$previous
		);
	}
}