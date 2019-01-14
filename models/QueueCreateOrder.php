<?php

namespace app\models;


use yii\base\Model;

/**
 * 队列创建订单时的校验模型
 * @package app\models
 *
 * @property $orderId
 * @property $status
 * @property $do
 * @property $openId
 * @property $bargainId
 * @property $address
 */
class QueueCreateOrder extends Model
{
    public $orderId;
    public $status;
    public $do;
    public $openId;
    public $bargainId;
    public $address;

    public function attributes()
    {
        return [
            'orderId', 'status', 'do', 'openId', 'bargainId', 'address'
        ];
    }

    public function rules()
    {
        return [
            [['orderId', 'status', 'do', 'openId', 'bargainId', 'address'], 'required'],
            [['orderId', 'do', 'openId', 'bargainId', 'address'], 'string'],
            [['status'], 'integer'],
        ];
    }
}