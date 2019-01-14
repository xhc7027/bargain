<?php

namespace app\services\growing;

/**
 * 定义漏斗数据对外模块
 * Interface GrowingFacade
 * @package app\services\event
 */
interface GrowingFacade
{
    /**
     * 向某个服务获取对应的growing数据
     * @param $url
     * @param $wxId
     * @return mixed
     */
    public static function getServiceGrowing($url, $wxId);

}