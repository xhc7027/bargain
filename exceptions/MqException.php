<?php

namespace app\exceptions;

/**
 * 消息服务异常
 * @package app\exceptions
 */
class MqException extends SystemException
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'MqException';
    }
}