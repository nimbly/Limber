<?php

namespace Limber\Exceptions;

use Symfony\Component\HttpFoundation\Response;

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
    protected $httpStatus = Response::HTTP_METHOD_NOT_ALLOWED;
}