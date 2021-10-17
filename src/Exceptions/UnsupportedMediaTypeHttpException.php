<?php

namespace Limber\Exceptions;

use Throwable;

/**
 * 415 Unsupported Media Type exception.
 */
class UnsupportedMediaTypeHttpException extends HttpException
{
	/**
	 * @inheritDoc
	 */
	protected $httpStatus = 415;

	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			$message ?? "Unsupported Media Type",
			$code ?? $this->httpStatus,
			$previous
		);
	}
}