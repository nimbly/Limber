<?php

namespace Limber\Exceptions;

use Exception;

abstract class HttpException extends Exception
{
    /**
     * HTTP status code
     *
     * @var int
     */
	protected $httpStatus;

	/**
	 * Additional headers to send with this exception.
	 *
	 * @var array<string, string>
	 */
	protected $headers = [];

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
	 * Get additional headers that should be sent with this exception.
	 *
	 * @return array<string, string>
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}
}