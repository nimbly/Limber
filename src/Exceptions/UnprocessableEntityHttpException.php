<?php

namespace Limber\Exceptions;

/**
 * 422 Unprocessable Entity exception.
 */
class UnprocessableEntityHttpException extends HttpException
{
	/**
	 * @inheritDoc
	 */
	protected $httpStatus = 422;
}