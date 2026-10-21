<?php

namespace Nimbly\Limber\Exceptions;

use Throwable;

/**
 * 422 Unprocessable Entity exception.
 */
class UnprocessableEntityHttpException extends HttpException
{
	/**
	 * UnprocessableEntityHttpException constructor.
	 *
	 * @param string|null $message
	 * @param integer|null $code
	 * @param Throwable|null $previous
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/422
	 */
	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			422,
			$message ?? "Unprocessable Entity",
			[],
			$code,
			$previous
		);
	}
}