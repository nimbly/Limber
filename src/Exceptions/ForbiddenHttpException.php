<?php

namespace Limber\Exceptions;

/**
 * 403 Forbidden exception.
 */
class ForbiddenHttpException extends HttpException
{
	/**
	 * @inheritDoc
	 */
	protected $httpStatus = 403;
}