<?php

namespace Limber\Exceptions;

/**
 * 400 Bad Request exception.
 */
class BadRequestHttpException extends HttpException
{
	/**
	 * @inheritDoc
	 */
	protected $httpStatus = 400;
}