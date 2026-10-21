<?php

namespace Nimbly\Limber\Exceptions;

use Throwable;

/**
 * 415 Unsupported Media Type exception.
 */
class UnsupportedMediaTypeHttpException extends HttpException
{
	/**
	 * UnsupportedMediaTypeHttpException constructor.
	 *
	 * @param string|null $message
	 * @param integer|null $code
	 * @param Throwable|null $previous
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/415
	 */
	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			415,
			$message ?? "Unsupported Media Type",
			[],
			$code,
			$previous
		);
	}
}