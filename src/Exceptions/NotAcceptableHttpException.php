<?php

namespace Nimbly\Limber\Exceptions;

use Throwable;

/**
 * 406 Not Acceptable exception.
 */
class NotAcceptableHttpException extends HttpException
{
	/**
	 * NotAcceptableHttpException constructor.
	 *
	 * @param string|null $message
	 * @param integer|null $code
	 * @param Throwable|null $previous
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/406
	 */
	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			406,
			$message ?? "Not Acceptable",
			[],
			$code,
			$previous
		);
	}
}