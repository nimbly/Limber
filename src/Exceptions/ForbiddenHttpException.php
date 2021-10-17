<?php

namespace Limber\Exceptions;

use Throwable;

/**
 * 403 Forbidden exception.
 */
class ForbiddenHttpException extends HttpException
{
	/**
	 * @inheritDoc
	 */
	protected $httpStatus = 403;

	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			$message ?? "Forbidden",
			$code ?? $this->httpStatus,
			$previous
		);
	}
}