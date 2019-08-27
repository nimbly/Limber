<?php

namespace Limber\Exceptions;

/**
 * 409 Conflict exception.
 */
class ConflictHttpException extends HttpException
{
	/**
	 * @inheritDoc
	 */
	protected $httpStatus = 409;
}