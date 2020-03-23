<?php

namespace Limber\Exceptions;

use Exception;

/**
 * 503 Service Unavailable exception.
 */
class ServiceUnavailableHttpException extends HttpException
{
	/**
	 * @inheritDoc
	 */
	protected $httpStatus = 503;

	/**
	 * ServiceUnavailableHttpException contructor
	 *
	 * This HTTP status requires a Retry-After header to be sent.
	 *
	 * @param string $retryAfter A string that is either an HTTP date (eg, Wed, 21 Oct 2015 07:28:00 GMT) or an integer (eg, 120) for the number of seconds to delay the retry by.
	 * @param string|null $message
	 * @param integer|null $code
	 * @param Exception|null $previous
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Retry-After
	 */
	public function __construct(string $retryAfter, ?string $message = null, ?int $code = null, ?Exception $previous = null)
	{
		$this->headers["Retry-After"] = $retryAfter;
		parent::__construct($message ?? "Service unavailable", $code ?? $this->httpStatus, $previous);
	}
}