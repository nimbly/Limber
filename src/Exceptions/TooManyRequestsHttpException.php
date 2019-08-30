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
	 * This HTTP status requires a Retry-After header to be sent.
	 *
	 * @param string $retryAfter
	 * @param string|null $message
	 * @param integer|null $code
	 * @param Exception|null $previous
	 */
	public function __construct(string $retryAfter, ?string $message = null, ?int $code = null, ?Exception $previous = null)
	{
		$this->headers["Retry-After"] = $retryAfter;
		parent::__construct($message ?? "Too many requests", $code ?? $this->httpStatus, $previous);
	}
}