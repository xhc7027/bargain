<?php

namespace app\services\data;

/**
 * 定义数据统计模块对外接口
 */
interface DataFacade
{
    /**
     * 导出excel的接口
     * @param $activityStatistic
     * @return mixed
     */
    public function getActivityExcel($activityStatistic);
}