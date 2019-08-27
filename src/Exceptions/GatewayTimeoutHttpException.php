<?php

namespace Limber\Exceptions;

/**
 * 504 Gateway Timeout exception.
 */
class GatewayTimeoutHttpException extends HttpException
{
	/**
	 * @inheritDoc
	 */
    protected $httpStatus = 504;
}