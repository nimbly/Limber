<?php

namespace Nimbly\Limber\Exceptions;

use Throwable;

/**
 * 429 Too Many Requests exception.
 */
class TooManyRequestsHttpException extends HttpException
{
	/**
	 * TooManyRequestsHttpException constructor
	 *
	 * This HTTP status *may* include a Retry-After header to be sent.
	 *
	 * @param string|null $retryAfter An integer representing the number of seconds the client should delay before sending their request again.
	 * @param string|null $message
	 * @param integer|null $code
	 * @param Throwable|null $previous
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/429
	 */
	public function __construct(?string $retryAfter = null, ?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			429,
			$message ?? "Too many requests",
			$retryAfter ? ["Retry-After" => $retryAfter] : [],
			$code,
			$previous
		);
	}
}