<?php

namespace Nimbly\Limber\Exceptions;

use Throwable;

/**
 * 408 Request Timeout exception.
 */
class RequestTimeoutHttpException extends HttpException
{
	/**
	 * RequestTimeoutHttpException constructor.
	 *
	 * @param string|null $message
	 * @param integer|null $code
	 * @param Throwable|null $previous
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/408
	 */
	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			408,
			$message ?? "Request Timeout",
			[],
			$code,
			$previous
		);
	}
}