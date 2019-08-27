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
	 * @param string $retryAfter
	 * @param string|null $message
	 * @param integer|null $code
	 * @param Exception|null $previous
	 */
	public function __construct(string $retryAfter, ?string $message = null, ?int $code = null, ?Exception $previous = null)
	{
		$this->headers["Retry-After"] = $retryAfter;
		parent::__construct($message, $code, $previous);
	}
}