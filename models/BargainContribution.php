<?php

namespace app\models;

use yii\base\Exception;
use yii\mongodb\ActiveRecord;
use Yii;

/**
 * 砍价贡献集合
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $bargainId
 * @property string $openId
 * @property string $headImg
 * @property string $nickName
 * @property int $bargainTime
 * @property double $beforePrice
 * @property double $afterPrice
 * @property double $diffPrice
 */
class BargainContribution extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function collectionName()
    {
        return 'bargain_contribution';
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return [
            '_id',
            'bargainId',
            'openId',
            'headImg',
            'nickName',
            'bargainTime',
            'beforePrice',
            'afterPrice',
            'diffPrice',
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['bargainId', 'openId', 'headImg', 'nickName', 'bargainTime', 'beforePrice', 'afterPrice', 'diffPrice'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            '_id' => 'ID',
            'bargainId' => 'Bargain ID',
            'openId' => 'Open ID',
            'headImg' => 'Head Img',
            'nickName' => 'Nick Name',
            'bargainTime' => 'Bargain Time',
            'beforePrice' => 'Before Price',
            'afterPrice' => 'After Price',
            'diffPrice' => 'Diff Price',
        ];
    }

    /**
     * 保存砍价数据之类的默认数据处理
     * @param $bargainContribution
     * @param $wxInfo
     * @throws Exception
     */
    public static function _beforeBargainSave($bargainContribution, $wxInfo)
    {
        if (!$bargainContribution) {
            throw new Exception('bargainContribution model can not be null');
        }
        $bargainContribution->openId = $wxInfo['openid'];
        $bargainContribution->headImg = $wxInfo['headimgurl'];
        $bargainContribution->nickName = $wxInfo['nickname'];
        $bargainContribution->bargainTime = time();
    }

    /**
     * 以数组的形式获取多个商城基础数据
     *
     * @param array $condition 查询条件
     * @param array $field 查询字段
     * @return array|null|ActiveRecord
     */
    public static function getBargainConAsArrayAll(array $condition, array $field = [])
    {
        return self::find()->select($field)->where($condition)->asArray()->all();
    }

}