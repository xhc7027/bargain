<?php

namespace app\models;

use yii\base\Exception;
use yii\mongodb\ActiveRecord;
use Yii;

/**
 * This is the model class for collection "event_statistics".
 *
 * @property \MongoId|string $_id
 * @property mixed $eventId
 * @property mixed $supplierId
 * @property mixed $date
 * @property mixed $pv
 */
class EventStatistics extends \yii\mongodb\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function collectionName()
    {
        return 'event_statistics';
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return [
            '_id',
            'eventId',
            'supplierId',
            'date',
            'pv',
            'uv'
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['_id', 'eventId', 'supplierId','date', 'pv', 'uv'], 'safe']
        ];
    }

    /**
     * <p>记录每天用户uv次数</p>
     * 如果找到当天商家的数据就修改，否则变成新增加。
     */
    public static function recordNumberOfEveryDayUv($eventId,$supplierId)
    {
        self::recordNumberOfEveryDayStatistics('uv',$eventId,$supplierId);
    }

    /**
     * <p>记录每天用户访问次数</p>
     * 如果找到当天商家的数据就修改，否则变成新增加。
     */
    public static function recordNumberOfEveryDayPv($eventId,$supplierId)
    {
        self::recordNumberOfEveryDayStatistics('pv',$eventId,$supplierId);
    }

    /**
     * @param string $filed
     * @param string|null $eventId
     * @param string|null $supplierId
     * @param int $time
     * @return bool
     */
    private static function recordNumberOfEveryDayStatistics(string $filed, string $eventId = null,
                                                             string $supplierId = null, int $time = -1): bool
    {
        if (-1 === $time) {
            $time = time();
        }
        $col = Yii::$app->mongodb->getCollection(parent::collectionName());
        $ret = $col->findAndModify(
            [
                'supplierId' => $supplierId,
                'date' => intval(date('Ymd', $time)),
                'eventId' => $eventId,
            ],
            [
                '$inc' => [$filed => 1],
            ],
            ['new' => 1, 'upsert' => 1]
        );
        return $ret ? true : false;
    }

}
