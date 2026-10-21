<?php

namespace Nimbly\Limber\Exceptions;

use Throwable;

/**
 * 504 Gateway Timeout exception.
 */
class GatewayTimeoutHttpException extends HttpException
{
	/**
	 * GatewayTimeoutHttpException constructor.
	 *
	 * @param string|null $message
	 * @param integer|null $code
	 * @param Throwable|null $previous
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/504
	 */
	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			504,
			$message ?? "Gateway Timeout",
			[],
			$code,
			$previous
		);
	}
}