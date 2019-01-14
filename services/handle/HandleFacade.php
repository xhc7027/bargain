<?php

namespace app\services\handle;

/**
 * 定义活动后期处理对外接口
 */
interface HandleFacade
{
    /**
     * 活动兑奖接口
     * @param string $bargainId
     * @return mixed
     */
    public static function doCashPrize(string $bargainId);
}