<?php
namespace app\models;

use yii\base\Model;
use Yii;
use yii\data\Pagination;

/**
 * 活动统计接口搜索查询以及导出excel所用到的模型
 * 该类主要用于验证和对不同场景做处理
 * Class ActivityStatisticForm
 * @package app\models
 */
class ActivityStatisticForm extends Model
{
    /**
     * @var -- 活动的id,string类型
     */
    public $eventId;

    /**
     * @var -- 活动的状态，
     * '未知状态',
     * '待付款',
     * '货到付款',
     * '已付款',
     * '已关闭',
     * '已发货',
     * '已完成',
     * '订单过期',
     * '已退款',
     * '商家已删除',
     * '退款中',
     * '退款中',
     * '未兑奖'，
     * '已兑奖',
     */
    public $resourceStatus;

    /**
     * @var -- 截止时间 int类型
     */
    public $endTime;

    /**
     * @var -- 开始时间 int类型
     */
    public $startTime;

    /**
     * @var -- 用户名或者电话，都是用的同一个参数
     */
    public $searchByNameOrPhone;

    /**
     * @var -- 是否是微商城商品 0-微商城 1-非微商城
     */
    public $isMall;

    /**
     * 验证规则
     * eventId在获取统计列表的时候是必须的
     * eventId,isMall在导出excel的时候是必须的
     * @return array
     */
    public function rules()
    {
        return [
            [['resourceStatus', 'endTime', 'startTime', 'searchByNameOrPhone'], 'safe'],
            ['eventId', 'required', 'on' => 'getActivityStatistic'],
            [['isMall', 'eventId'], 'required', 'on' => 'getActivityExcel']
        ];
    }

    /**
     * 设置两个不同的场景
     * @return array
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        //设置活动统计列表的时候的场景
        $scenarios['getActivityStatistic'] = [
            'resourceStatus', 'endTime', 'startTime', 'searchByNameOrPhone', 'eventId'
        ];
        //设置导出excel的时候的场景
        $scenarios['getActivityExcel'] = [
            'resourceStatus', 'endTime', 'startTime', 'searchByNameOrPhone', 'eventId', 'isMall'
        ];
        return $scenarios;
    }

}