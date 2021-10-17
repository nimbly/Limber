<?php

namespace Limber\Exceptions;

use Throwable;

/**
 * 502 Bad Gateway exception.
 */
class BadGatewayHttpException extends HttpException
{
	/**
	 * @inheritDoc
	 */
	protected $httpStatus = 502;

	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			$message ?? "Bad Gateway",
			$code ?? $this->httpStatus,
			$previous
		);
	}
}