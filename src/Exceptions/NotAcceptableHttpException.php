<?php

namespace Limber\Exceptions;

/**
 * 406 Not Acceptable exception.
 */
class NotAcceptableHttpException extends HttpException
{
	/**
	 * @inheritDoc
	 */
	protected $httpStatus = 406;
}