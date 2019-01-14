<?php

namespace app\services\event;

/**
 * 定义活动管理模块对外接口
 */
interface EventFacade
{
    /**
     * 活动页面数据获取
     * @param string|null $eventId 砍价活动id
     * @param string $from 判断是来自创建还是编辑还是复制：create代表创建，edit代表编辑，copy代表复制
     * @return mixed
     */
    public function getBargainData($eventId, string $from);

    /**
     * 返回新建活动时的默认数据
     * @return array|yii\mongodb\ActiveRecord
     * @throws Exception
     */
    public  function createBargain();

    /**
     * 返回编辑活动时的默认数据
     * @return array|yii\mongodb\ActiveRecord
     * @throws Exception
     */
    public function editBargain();

    /**
     * 返回复制活动时的默认数据
     * @return RespMsg
     * @throws Exception
     */
    public function copyBargain();

    /**
     * 删除活动接口，逻辑删除
     * @param string $eventId
     * @return mixed
     */
    public static function deleteEvent(string $eventId);

    /**
     * 关闭活动接口，逻辑关闭
     * @param string $eventId
     * @return mixed
     */
    public static function closeEvent(string $eventId);

    /**
     * 获取活动列表数据接口
     * @param int $supplierId
     * @return mixed
     */
    public function getActivityList(int $supplierId);

    /**
     * 获取活动统计列表数据接口
     * @param  $activityStatistic
     * @return mixed
     */
    public function getActivityStatistic($activityStatistic);

    /**
     * 用于组装返回的最后数据
     * @param $eventData
     * @return RespMsg
     */
    public  function assembleEventData($eventData);

    /**
     * 去idouzi 查询关键字
     * 增删改查 分别为 1 查询 2储存 3修改 4删除
     * @param string $keyword
     * @param int $wxId
     * @param string $event_id
     * @param string $numeric
     * @return array json格式数据json_encode(array("status"=>1,"msg"=>'ok'));
     * ps: status = 1操作成功，0操作失败。
     */
    public static function ChecklistKeyword(string $keyword, int $wxId, string $event_id, string $numeric);
}