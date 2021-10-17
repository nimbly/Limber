<?php

namespace Limber\Exceptions;

use Throwable;

/**
 * 410 Gone exception.
 */
class GoneHttpException extends HttpException
{
	/**
	 * @inheritDoc
	 */
	protected $httpStatus = 410;

	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			$message ?? "Gone",
			$code ?? $this->httpStatus,
			$previous
		);
	}
}