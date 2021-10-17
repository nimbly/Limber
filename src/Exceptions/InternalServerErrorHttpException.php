<?php

namespace Limber\Exceptions;

use Throwable;

/**
 * 500 Internal Server Error exception
 */
class InternalServerErrorHttpException extends HttpException
{
	/**
	 * @inheritDoc
	 */
	protected $httpStatus = 500;

	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			$message ?? "Internal Server Error",
			$code ?? $this->httpStatus,
			$previous
		);
	}
}