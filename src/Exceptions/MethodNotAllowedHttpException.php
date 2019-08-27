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
	 * @param array<string> $methodsAllowed
	 * @param string|null $message
	 * @param integer|null $code
	 * @param Exception|null $previous
	 */
	public function __construct(array $methodsAllowed, ?string $message = null, ?int $code = null, ?Exception $previous = null)
	{
		$this->headers['Allow'] = \implode(", ", $methodsAllowed);
		parent::__construct($message, $code, $previous);
	}
}