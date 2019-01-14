<?php

namespace app\exceptions;

/**
 * 网络异常
 * @package app\exceptions
 */
class HttpException extends SystemException
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'HttpException';
    }
}