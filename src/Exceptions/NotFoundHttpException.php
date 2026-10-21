<?php

namespace Nimbly\Limber\Exceptions;

use Throwable;

/**
 * 404 Not Found exception.
 */
class NotFoundHttpException extends HttpException
{
	/**
	 * NotFoundHttpException constructor.
	 *
	 * @param string|null $message
	 * @param integer|null $code
	 * @param Throwable|null $previous
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/404
	 */
	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			404,
			$message ?? "Not Found",
			[],
			$code,
			$previous
		);
	}
}