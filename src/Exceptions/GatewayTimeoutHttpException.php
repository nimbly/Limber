<?php

namespace Limber\Exceptions;

use Throwable;

/**
 * 504 Gateway Timeout exception.
 */
class GatewayTimeoutHttpException extends HttpException
{
	/**
	 * @inheritDoc
	 */
	protected $httpStatus = 504;

	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			$message ?? "Gateway Timeout",
			$code ?? $this->httpStatus,
			$previous
		);
	}
}