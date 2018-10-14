<?php

namespace Limber\Exception;

abstract class HttpException extends \Exception
{
    protected $httpStatus;

    /**
     * Get the HTTP status
     *
     * @return integer
     */
    public function getHttpStatus()
    {
        return $this->httpStatus;
    }

}