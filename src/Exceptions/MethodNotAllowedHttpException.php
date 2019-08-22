<?php

namespace Limber\Exceptions;

/**
 * 405 Method Not Allowed exception.
 */
class MethodNotAllowedHttpException extends HttpException
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $httpStatus = 405;
}