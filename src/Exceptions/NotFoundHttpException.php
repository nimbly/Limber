<?php

namespace Limber\Exceptions;

use Throwable;

/**
 * 404 Not Found exception.
 */
class NotFoundHttpException extends HttpException
{
	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			404,
			$message ?? "Not Found",
			[],
			$code,
			$previous
		);
	}
}