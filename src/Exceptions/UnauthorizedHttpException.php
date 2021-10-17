<?php

namespace Limber\Exceptions;

use Throwable;

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
	 * @param string $authMethod Information on how the client should authorize.
	 * @param string|null $message
	 * @param integer|null $code
	 * @param Throwable|null $previous
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/401
	 */
	public function __construct(string $authMethod, ?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		$this->headers["WWW-Authenticate"] = $authMethod;

		parent::__construct(
			$message ?? "Unauthorized",
			$code ?? $this->httpStatus,
			$previous
		);
	}
}