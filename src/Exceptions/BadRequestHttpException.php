<?php

namespace Nimbly\Limber\Exceptions;

use Throwable;

/**
 * 400 Bad Request exception.
 */
class BadRequestHttpException extends HttpException
{
	/**
	 * BadRequestHttpException constructor.
	 *
	 * @param string|null $message
	 * @param integer|null $code
	 * @param Throwable|null $previous
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/400
	 */
	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			400,
			$message ?? "Bad Request",
			[],
			$code,
			$previous
		);
	}
}