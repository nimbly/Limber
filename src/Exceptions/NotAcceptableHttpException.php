<?php

namespace Limber\Exceptions;

use Throwable;

/**
 * 406 Not Acceptable exception.
 */
class NotAcceptableHttpException extends HttpException
{
	/**
	 * @inheritDoc
	 */
	protected $httpStatus = 406;

	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			$message ?? "Not Acceptable",
			$code ?? $this->httpStatus,
			$previous
		);
	}
}