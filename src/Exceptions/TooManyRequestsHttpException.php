<?php

namespace Limber\Exceptions;

use Exception;

/**
 * 429 Too Many Requests exception.
 */
class TooManyRequestsHttpException extends HttpException
{
	/**
	 * @inheritDoc
	 */
	protected $httpStatus = 429;

	/**
	 * TooManyRequestsHttpException constructor
	 *
	 * This HTTP status *may* include a Retry-After header to be sent.
	 *
	 * @param string $retryAfter An integer representing the number of seconds the client should delay before sending their request again.
	 * @param string|null $message
	 * @param integer|null $code
	 * @param Exception|null $previous
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/429
	 */
	public function __construct(?string $retryAfter = null, ?string $message = null, ?int $code = null, ?Exception $previous = null)
	{
		if( $retryAfter ){
			$this->headers["Retry-After"] = $retryAfter;
		}

		parent::__construct($message ?? "Too many requests", $code ?? $this->httpStatus, $previous);
	}
}