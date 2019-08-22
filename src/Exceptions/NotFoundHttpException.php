<?php

namespace Limber\Exceptions;

/**
 * 404 Not Found exception.
 */
class NotFoundHttpException extends HttpException
{
    /**
     * HTTP status code.
     *
     * @var int
     */
    protected $httpStatus = 404;
}