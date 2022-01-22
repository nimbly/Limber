<?php

namespace Nimbly\Limber\Exceptions;

use Throwable;

/**
 * 401 Unauthorized exception.
 */
class UnauthorizedHttpException extends HttpException
{
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
		parent::__construct(
			401,
			$message ?? "Unauthorized",
			["WWW-Authenticate" => $authMethod],
			$code,
			$previous
		);
	}
}