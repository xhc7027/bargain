<?php

namespace app\models;

use yii\mongodb\ActiveRecord;
use Idouzi\Commons\HashClient;
use Yii;
use Idouzi\Commons\exceptions\SystemException;

/**
 * This is the model class for collection "event_statistics".
 *
 * @property \MongoId|string $_id
 * @property mixed $eventId
 * @property mixed $supplierId
 * @property mixed $date
 * @property mixed $openId
 */
class EventUvRecord extends ActiveRecord
{
    public $date;

    /**
     * 获取表的真实表名
     *
     * @throws SystemException
     * @return string
     */
    public function getTableName()
    {
        if (!$this->date) {
            throw new SystemException('计算表名出错，因为uid是空的');
        }

        //计算出具体的分表号
        $subTableNumber = HashClient::lookup($this->date, 20);

        //构造成表结构
        $tableName = 'event_uv_record_' . $subTableNumber;
        return $tableName;
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
            'openId',
            'date',
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['_id', 'eventId', 'supplierId', 'openId', 'date'], 'safe']
        ];
    }


    /**
     * @param string|null $eventId
     * @param string|null $supplierId
     * @param string|null $openId
     * @return bool
     */
    public function insertOpenId(string $supplierId = null, string $eventId = null, string $openId = null): bool
    {
        $collection = Yii::$app->mongodb->getCollection($this->getTableName());
        $this->eventId = $eventId;
        $this->supplierId = $supplierId;
        $this->openId = $openId;
        $this->date;
        $data = $this->attributes;
        unset($data['_id']);
        if (!$collection->insert($data)) {
            return false;
            Yii::warning($eventId . '添加openId到数据库失败!');
        }
        return true;

    }

    /**
     * 分表并批量插入数据
     *
     * @param $eventPvData
     * @return bool
     * @throws SystemException
     */
    public function getBatchInsert($eventPvData)
    {
        if (!$eventPvData) {
            return false;
        }
        if (!Yii::$app->mongodb->getCollection($this->getTableName())->batchInsert($eventPvData)) {
            throw new SystemException('eventPV分表批量插入数据失败，error:' . '插入失败数据' . $eventPvData);
        }
    }

}
