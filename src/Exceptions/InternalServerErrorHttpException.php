<?php

namespace Nimbly\Limber\Exceptions;

use Throwable;

/**
 * 500 Internal Server Error exception
 */
class InternalServerErrorHttpException extends HttpException
{
	/**
	 * InternalServerErrorHttpException constructor.
	 *
	 * @param string|null $message
	 * @param integer|null $code
	 * @param Throwable|null $previous
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/500
	 */
	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			500,
			$message ?? "Internal Server Error",
			[],
			$code,
			$previous
		);
	}
}