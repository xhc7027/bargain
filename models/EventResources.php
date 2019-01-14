<?php

namespace app\models;

use yii\base\Model;

/**
 * 活动设置的砍价资源
 *
 * @property string $name
 * @property int $number
 * @property string $type
 * @property string $id
 * @property string $mallId
 * @property double $price
 * @package app\models
 */
class EventResources extends Model
{
    /**
     * @var string 商品名称
     */
    public $name;

    /**
     * @var int 商品数量
     */
    public $number;

    /**
     * @var string 标识是否为微商城商品,0微商城商品，1线下渠道交易商品
     */
    public $type;

    /**
     * @var int 商品编号，如果是微商城商品会有此值
     */
    public $id;

    /**
     * @var int 商城id，如果是微商城商品会有此值
     */
    public $mallId;

    /**
     * @var int 原价
     */
    public $price;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['id', 'name', 'type', 'price', 'number', 'mallId'], 'safe'],
            [['number'], 'integer', 'max' => 99999999],
            [['type'], 'in', 'range' => [0, 1]],
            [['name'], 'string', 'length' => [1, 30], 'message' => '商品名称不能超过30个字'],
            [['price', 'type', 'number'], 'required', 'on' => ['create']],
        ];
    }

    /**
     * 场景设置
     * @return array
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();//本行必填，不写的话就会报如上错误
        $scenarios['create'] = ['id', 'name', 'type', 'price', 'number', 'mallId'];
        $scenarios['edit'] = [];
        return $scenarios;
    }

    /**
     * 在业务上做字段校验
     *
     * @param null $attributeNames
     * @param bool $clearErrors
     * @return bool
     */
    public function validate($attributeNames = null, $clearErrors = true)
    {
        if (parent::validate($attributeNames, $clearErrors)) {
            if ($this->type == 0 && $this->getScenario() == 'create') {//如果是微商城商品，但是没有商品编号
                if (!$this->id) {
                    $this->addError('id', '商城商品编号为空!');
                    return false;
                }
                if (!$this->mallId) {
                    $this->addError('mallId', '商城id为空!');
                    return false;
                }
            }
            if ($this->getScenario() == 'create' && $this->number < 1) {
                $this->addError('number', '商品库存不能为0!');
                return false;
            }
            return true;
        }
        return false;
    }
}