<?php

namespace Limber\Exceptions;

use Exception;

/**
 * 405 Method Not Allowed exception.
 */
class MethodNotAllowedHttpException extends HttpException
{
    /**
     * @inheritDoc
     */
	protected $httpStatus = 405;

	/**
	 * MethodNotAllowedHttpException constructor.
	 *
	 * This HTTP status code requires a list of HTTP methods that *are* allowed on the resource.
	 *
	 * @param array<string> $methodsAllowed An array of strings representing the HTTP methods that are allowed on the resource.
	 * @param string|null $message
	 * @param integer|null $code
	 * @param Exception|null $previous
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/405
	 */
	public function __construct(array $methodsAllowed, ?string $message = null, ?int $code = null, ?Exception $previous = null)
	{
		$this->headers['Allow'] = \implode(", ", $methodsAllowed);
		parent::__construct($message ?? "Method not allowed", $code ?? $this->httpStatus, $previous);
	}
}