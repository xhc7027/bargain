<?php

namespace app\models;

use yii\mongodb\ActiveRecord;

/**
 * 活动说明集合
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $eventId
 * @property string $content
 */
class EventDescription extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function collectionName()
    {
        return 'event_description';
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return [
            '_id',
            'eventId',
            'content',
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['eventId', 'content'], 'safe'],
            [['content'], 'required'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            '_id' => 'ID',
            'eventId' => 'Event ID',
            'content' => 'Content',
        ];
    }

    /**
     * @param string $eventId
     * @return string
     */
    public static function getDescriptionByEventId(string $eventId): string
    {
        $data = self::find()->select(['content'])->where(['eventId' => $eventId])->asArray()->one();
        if ($data) {
            return $data['content'];
        }
        return null;
    }
}