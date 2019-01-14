<?php

namespace app\services\bargain;

/**
 * 定义砍价模块对外接口
 */
interface BargainFacade
{
    /**
     * 计算砍价次数
     * @param array $bargainProbabilityData
     * @param $price
     * @param $lowestPrice
     * @return array
     */
    public static function calculateBargainTimes(array $bargainProbabilityData, $price, $lowestPrice): array;

    /**
     * 返回计算刀数后，使数据以int返回
     * @param float $num
     * @return int
     */
    public static function returnInt(float $num): int;

    /**
     * 砍到的价格
     * @param array $bargainProbability
     * @return array
     */
    public static function changePrice(array $bargainProbability);

    /**
     * 返回这次是否降价
     * @param $priceReduction
     * @return int -1为降价，1为涨价, 为价格正负符号
     */
    public static function reducePrice($priceReduction);

    /**
     * 执行砍价
     * @param $bargain 商家发起的活动砍价Id
     * @return RespMsg
     */
    public static function bargain($bargain);

    /**
     * 获取砍价贡献列表
     * @param string $bargainId--用户参与活动的id
     * @return mixed
     */
    public function getHelperList(string $bargainId);
}