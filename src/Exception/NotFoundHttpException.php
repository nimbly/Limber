<?php

namespace Limber\Exception;

use Symfony\Component\HttpFoundation\Response;

/**
 * Undocumented class
 */
class NotFoundHttpException extends HttpException
{
    protected $httpStatus = Response::HTTP_NOT_FOUND;
}