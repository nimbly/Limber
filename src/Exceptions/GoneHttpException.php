<?php

namespace Nimbly\Limber\Exceptions;

use Throwable;

/**
 * 410 Gone exception.
 */
class GoneHttpException extends HttpException
{
	/**
	 * GoneHttpException constructor.
	 *
	 * @param string|null $message
	 * @param integer|null $code
	 * @param Throwable|null $previous
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/410
	 */
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