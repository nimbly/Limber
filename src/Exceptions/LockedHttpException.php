<?php

namespace Nimbly\Limber\Exceptions;

use Throwable;

/**
 * 423 Locked exception.
 */
class LockedHttpException extends HttpException
{
	/**
	 * LockedHttpException constructor.
	 *
	 * @param string|null $message
	 * @param integer|null $code
	 * @param Throwable|null $previous
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/423
	 */
	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			423,
			$message ?? "Locked",
			[],
			$code,
			$previous
		);
	}
}