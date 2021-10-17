<?php

namespace Limber\Exceptions;

use Throwable;

/**
 * 409 Conflict exception.
 */
class ConflictHttpException extends HttpException
{
	/**
	 * @inheritDoc
	 */
	protected $httpStatus = 409;

	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			$message ?? "Conflict",
			$code ?? $this->httpStatus,
			$previous
		);
	}
}