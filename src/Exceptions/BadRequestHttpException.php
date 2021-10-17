<?php

namespace Limber\Exceptions;

use Throwable;

/**
 * 400 Bad Request exception.
 */
class BadRequestHttpException extends HttpException
{
	/**
	 * @inheritDoc
	 */
	protected $httpStatus = 400;

	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			$message ?? "Bad Request",
			$code ?? $this->httpStatus,
			$previous
		);
	}
}