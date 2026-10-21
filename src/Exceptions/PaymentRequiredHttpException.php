<?php

namespace Nimbly\Limber\Exceptions;

use Throwable;

/**
 * 402 Payment Required exception.
 */
class PaymentRequiredHttpException extends HttpException
{
	/**
	 * PaymentRequiredHttpException constructor.
	 *
	 * @param string|null $message
	 * @param integer|null $code
	 * @param Throwable|null $previous
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/402
	 */
	public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			402,
			$message ?? "Payment Required",
			[],
			$code,
			$previous
		);
	}
}