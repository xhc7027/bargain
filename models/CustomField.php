<?php

namespace app\models;

use yii\base\Exception;
use yii\mongodb\ActiveRecord;

/**
 * 自定义字段集合
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $label
 * @property string $name
 * @property array $rules
 */
class CustomField extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function collectionName()
    {
        return 'custom_field';
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return [
            '_id',
            'label',
            'name',
            'rules',
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['label', 'name', 'rules'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            '_id' => 'ID',
            'label' => 'Label',
            'name' => 'Name',
            'rules' => 'Rules',
        ];
    }


    /**
     * 根据name值获取自定义字段的_id值
     * @param array $names
     * @return array
     * @throws Exception
     */
    public static function getCustomFieldIdByNames(array $names): array
    {
        $ids = [];
        if (count($names) <= 0) {
            throw new Exception('自定义字段传入值不能为空');
        }
        foreach ($names as $name) {
            $id = self::find()->select(['_id'])->where(['name' => $name])->one();
            if ($id) {
                $ids[] = $id['_id']->__toString();
            }
        }
        return $ids;
    }

}