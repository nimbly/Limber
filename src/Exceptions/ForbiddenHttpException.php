<?php

namespace Nimbly\Limber\Exceptions;

use Throwable;

/**
 * 403 Forbidden exception.
 */
class ForbiddenHttpException extends HttpException
{
	/**
	 * ForbiddenHttpException constructor.
	 *
	 * @param string|null $message
	 * @param integer|null $code
	 * @param Throwable|null $previous
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/403
	 */
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