<?php

namespace app\models;

use yii\base\Model;

/**
 * 活动创建者信息
 *
 * @property int $id
 * @property string $headImg
 * @property string $nickName
 * @property string $qrcodeUrl
 * @package app\models
 */
class EventFounder extends Model
{

    /**
     * @var int 商家编号
     */
    public $id;

    /**
     * @var string 头像
     */
    public $headImg;

    /**
     * @var string 昵称
     */
    public $nickName;

    /**
     * @var string 公众号二维码图片链接
     */
    public $qrcodeUrl;

    /**
     * @var string 公众号id
     */
    public $appId;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['id', 'headImg', 'nickName', 'qrcodeUrl', 'appId'], 'safe'],
            [['id'], 'integer'],
            [['headImg', 'qrcodeUrl', 'nickName'], 'string'],
            [['id'], 'required', 'on' => ['create']],
        ];
    }

    /**
     * 场景设置
     * @return array
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();//本行必填，不写的话就会报如上错误
        $scenarios['create'] = ['id', 'headImg', 'nickName', 'qrcodeUrl'];
        $scenarios['edit'] = [];
        return $scenarios;
    }
}