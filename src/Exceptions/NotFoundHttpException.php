<?php

namespace Limber\Exceptions;

use Throwable;

/**
 * 404 Not Found exception.
 */
class NotFoundHttpException extends HttpException
{
	/**
	 * @inheritDoc
	 */
	protected $httpStatus = 404;

	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			$message ?? "Not Found",
			$code ?? $this->httpStatus,
			$previous
		);
	}
}