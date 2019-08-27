<?php

namespace Limber\Exceptions;

/**
 * 502 Bad Gateway exception.
 */
class BadGatewayHttpException extends HttpException
{
	/**
	 * @inheritDoc
	 */
    protected $httpStatus = 502;
}