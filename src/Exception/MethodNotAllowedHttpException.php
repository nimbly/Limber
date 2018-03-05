<?php

namespace Limber\Exception;

use Symfony\Component\HttpFoundation\Response;

class MethodNotAllowedHttpException extends HttpException
{
    protected $httpStatus = Response::HTTP_METHOD_NOT_ALLOWED;
}