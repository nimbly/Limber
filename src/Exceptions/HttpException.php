<?php

namespace Limber\Exceptions;

use Exception;
use Throwable;

abstract class HttpException extends Exception
{
	/**
	 * HTTP status code
	 *
	 * @var int
	 */
	protected int $httpStatus;

	/**
	 * Additional headers to send with this exception.
	 *
	 * @var array<string,string>
	 */
	protected array $headers = [];

	/**
	 * @param string $message
	 * @param integer $code
	 * @param Throwable|null $previous
	 */
	public function __construct(int $httpStatus, string $message, array $headers = [], ?int $code = null, ?Throwable $previous = null)
	{
		$this->httpStatus = $httpStatus;
		$this->headers = $headers;
		parent::__construct($message, $code ?? $httpStatus, $previous);
	}

	/**
	 * Get the HTTP status code.
	 *
	 * @return integer
	 */
	public function getHttpStatus(): int
	{
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