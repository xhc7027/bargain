<?php

namespace app\models;

use app\exceptions\SystemException;
use yii\mongodb\ActiveRecord;

/**
 * 活动砍价概率集合
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $eventId
 * @property double $priceReduction
 * @property array $priceReductionRange
 * @property double $priceIncrease
 * @property array $priceIncreaseRange
 */
class BargainProbability extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function collectionName()
    {
        return 'bargain_probability';
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return [
            '_id',
            'eventId',
            'priceReduction',
            'priceReductionRange',
            'priceIncrease',
            'priceIncreaseRange',
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['eventId', 'priceReduction', 'priceReductionRange', 'priceIncrease', 'priceIncreaseRange'], 'safe'],
            [['priceReduction', 'priceReductionRange', 'priceIncrease', 'priceIncreaseRange'], 'required'],
            [['priceReduction', 'priceIncrease'], 'double', 'min' => 0, 'max' => 1],
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
            'priceReduction' => 'Price Reduction',
            'priceReductionRange' => 'Price Reduction Range',
            'priceIncrease' => 'Price Increase',
            'priceIncreaseRange' => 'Price Increase Range',
        ];
    }

    /**
     * 将某些数据转为数字类型
     * @param array $data
     * @param null $formName
     * @return bool
     */
    public function load($data, $formName = null)
    {
        if (!parent::load($data, $formName)) {
            $this->addError('parent', current(parent::getFirstErrors()));
            return false;
        }
        $realData = $data;

        $this->priceReduction = (double)$realData['priceReduction'];
        $this->priceIncrease = (double)$realData['priceIncrease'];
        if (is_array($realData['priceReductionRange']) && self::checkIntInArray($realData['priceReductionRange'])) {
            foreach ($realData['priceReductionRange'] as &$value) {
                $value = (double)$value;
            }
        } else {
            $this->addError('priceReductionRange', '请填写降价范围');
            return false;
        }
        if (is_array($realData['priceIncreaseRange']) && self::checkIntInArray($realData['priceIncreaseRange'])) {
            foreach ($realData['priceIncreaseRange'] as &$val) {
                $val = (double)$val;
            }
        } else {
            $this->addError('priceIncreaseRange', '请填写涨价范围');
            return false;
        }
        return true;

    }

    public function validate($attributeNames = null, $clearErrors = true)
    {
        if (!parent::validate($attributeNames, $clearErrors)) {
            $this->addError('parent', current(parent::getFirstErrors()));
            return false;
        }
        if ($this->priceReduction + $this->priceIncrease != 1) {
            $this->addError('priceReduction and priceIncrease', '涨价和降价概率合计不为100%');
            return false;
        }
        return true;
    }

    /**
     * 用于校验降价涨价范围里面的数据是否为数字类型
     * @param $array
     * @return bool
     */
    private static function checkIntInArray($array)
    {
        if (count($array) != 2) {
            return false;
        }
        foreach ($array as $key => $value) {
            if (!is_numeric($value)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 通过砍价id获取砍价概率数据
     * @param string $eventId
     * @return array
     * @throws SystemException
     */
    public static function getProbabilityById(string $eventId): array
    {
        $probability = self::find()->select(
            ['_id' => null, 'eventId', 'priceReduction',
                'priceReductionRange', 'priceIncrease', 'priceIncreaseRange'
            ])
            ->where(['eventId' => $eventId])->asArray()->one();
        if (!$probability) {//如果没有数据，返回默认配置
            throw new SystemException('活动' . $eventId . '砍价概率不存在', __METHOD__);
        }
        return $probability;
    }
}