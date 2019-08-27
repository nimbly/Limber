<?php

namespace Limber\Exceptions;

/**
 * 500 Internal Server Error exception
 */
class InternalServerErrorHttpException extends HttpException
{
	/**
	 * @inheritDoc
	 */
	protected $httpStatus = 500;
}