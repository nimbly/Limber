<?php

namespace Limber\Exceptions;

/**
 * 404 Not Found exception.
 */
class NotFoundHttpException extends HttpException
{
	/**
	 * @inheritDoc
	 */
    protected $httpStatus = 404;
}