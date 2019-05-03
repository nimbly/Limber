<?php

namespace Limber\Exceptions;

abstract class HttpException extends \Exception
{
    /**
     * HTTP status code
     *
     * @var int
     */
    protected $httpStatus;

    /**
     * Get the HTTP status code.
     *
     * @return integer
     */
    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }
}