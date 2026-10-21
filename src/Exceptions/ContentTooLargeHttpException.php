<?php

namespace Nimbly\Limber\Exceptions;

use Throwable;

/**
 * 413 Content Too Large exception.
 */
class ContentTooLargeHttpException extends HttpException
{
	/**
	 * ContentTooLargeHttpException constructor.
	 *
	 * @param string|null $message
	 * @param integer|null $code
	 * @param Throwable|null $previous
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/413
	 */
	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			413,
			$message ?? "Content Too Large",
			[],
			$code,
			$previous
		);
	}
}