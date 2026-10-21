<?php

namespace Nimbly\Limber\Exceptions;

use Throwable;

/**
 * 502 Bad Gateway exception.
 */
class BadGatewayHttpException extends HttpException
{
	/**
	 * BadGatewayHttpException constructor.
	 *
	 * @param string|null $message
	 * @param integer|null $code
	 * @param Throwable|null $previous
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/502
	 */
	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			502,
			$message ?? "Bad Gateway",
			[],
			$code,
			$previous
		);
	}
}