<?php

namespace Limber\Exceptions;

use Throwable;

/**
 * 422 Unprocessable Entity exception.
 */
class UnprocessableEntityHttpException extends HttpException
{
	/**
	 * @inheritDoc
	 */
	protected $httpStatus = 422;

	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			$message ?? "Unprocessable Entity",
			$code ?? $this->httpStatus,
			$previous
		);
	}
}