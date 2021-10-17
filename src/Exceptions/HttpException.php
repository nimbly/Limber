<?php

namespace Limber\Exceptions;

use Exception;
use Throwable;
use UnexpectedValueException;

abstract class HttpException extends Exception
{
	/**
	 * HTTP status code
	 *
	 * @var int|null
	 */
	protected $httpStatus;

	/**
	 * Additional headers to send with this exception.
	 *
	 * @var array<string,string>
	 */
	protected $headers = [];

	/**
	 * @param string $message
	 * @param integer $code
	 * @param Throwable|null $previous
	 */
	public function __construct(string $message, int $code, ?Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}

	/**
	 * Get the HTTP status code.
	 *
	 * @return integer
	 */
	public function getHttpStatus(): int
	{
		if( empty($this->httpStatus) ){
			throw new UnexpectedValueException("HTTP status code has not been set");
		}

		return $this->httpStatus;
	}

	/**
	 * Get additional headers that should be sent with the response.
	 *
	 * @return array<string,string>
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}
}