<?php

namespace Limber\Exceptions;

use Throwable;

/**
 * 405 Method Not Allowed exception.
 */
class MethodNotAllowedHttpException extends HttpException
{
	/**
	 * MethodNotAllowedHttpException constructor.
	 *
	 * This HTTP status code requires a list of HTTP methods that *are* allowed on the resource.
	 *
	 * @param array<string> $methodsAllowed An array of strings representing the HTTP methods that are allowed on the resource.
	 * @param string|null $message
	 * @param integer|null $code
	 * @param Throwable|null $previous
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/405
	 */
	public function __construct(array $methodsAllowed, ?string $message = null, ?int $code = null, ?Throwable $previous = null)
	{
		parent::__construct(
			405,
			$message ?? "Method not allowed",
			["Allow" => \implode(", ", $methodsAllowed)],
			$code,
			$previous
		);
	}
}