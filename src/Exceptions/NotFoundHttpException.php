<?php

namespace Limber\Exceptions;

use Symfony\Component\HttpFoundation\Response;

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
    protected $httpStatus = Response::HTTP_NOT_FOUND;
}