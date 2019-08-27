<?php

namespace Limber\Exceptions;

use Exception;

/**
 * 401 Unauthorized exception.
 */
class UnauthorizedHttpException extends HttpException
{
	/**
	 * @inheritDoc
	 */
	protected $httpStatus = 401;

	/**
	 * UnauthorizedHttpException constructor
	 *
	 * This HTTP status code requires the WWW-Authenticate header to be sent.
	 *
	 * @param string $authMethod
	 * @param string|null $message
	 * @param integer|null $code
	 * @param Exception|null $previous
	 */
	public function __construct(string $authMethod, ?string $message = null, ?int $code = null, ?Exception $previous = null)
	{
		$this->headers["WWW-Authenticate"] = $authMethod;
		parent::__construct($message, $code, $previous);
	}
}