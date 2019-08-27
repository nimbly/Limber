<?php

namespace Limber\Exceptions;

/**
 * 410 Gone exception.
 */
class GoneHttpException extends HttpException
{
	/**
	 * @inheritDoc
	 */
    protected $httpStatus = 410;
}