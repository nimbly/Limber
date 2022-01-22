<?php

namespace Nimbly\Limber\Exceptions;

use Throwable;

/**
 * 415 Unsupported Media Type exception.
 */
class UnsupportedMediaTypeHttpException extends HttpException
{
	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			415,
			$message ?? "Unsupported Media Type",
			[],
			$code,
			$previous
		);
	}
}