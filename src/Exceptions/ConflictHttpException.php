<?php

namespace Nimbly\Limber\Exceptions;

use Throwable;

/**
 * 409 Conflict exception.
 */
class ConflictHttpException extends HttpException
{
	/**
	 * ConflictHttpException constructor.
	 *
	 * @param string|null $message
	 * @param integer|null $code
	 * @param Throwable|null $previous
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/409
	 */
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