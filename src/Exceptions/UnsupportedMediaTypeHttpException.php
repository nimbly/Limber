<?php

namespace Limber\Exceptions;

/**
 * 415 Unsupported Media Type exception.
 */
class UnsupportedMediaTypeHttpException extends HttpException
{
	/**
	 * @inheritDoc
	 */
	protected $httpStatus = 415;
}