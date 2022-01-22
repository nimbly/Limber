<?php

namespace Nimbly\Limber\Exceptions;

use Throwable;

/**
 * 406 Not Acceptable exception.
 */
class NotAcceptableHttpException extends HttpException
{
	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			406,
			$message ?? "Not Acceptable",
			[],
			$code,
			$previous
		);
	}
}