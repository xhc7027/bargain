<?php

namespace app\models;

use yii\base\Model;

/**
 * 活动创建者信息
 *
 * @property string $title 活动标题
 * @property string $image 回复图片
 * @property string $description 回复内容
 * @property string $shareTitle 分享标题
 * @property string $shareImage 分享图片
 * @property string $shareContent 分享内容
 * @property string $keyword 关键字
 * @package app\models
 */
class EventAdvancedSetting extends Model
{

    /**
     * @var string 活动标题
     */
    public $title;

    /**
     * @var string 活动图片
     */
    public $image;

    /**
     * @var string 活动说明
     */
    public $description;

    /**
     * @var string 分享标题
     */
    public $shareTitle;

    /**
     * @var string 分享图片
     */
    public $shareImage;

    /**
     * @var string 分享说明
     */
    public $shareContent;

    /**
     * @var string 关键字
     */
    public $keyword;


    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['title', 'image', 'description', 'shareTitle', 'shareImage', 'shareContent', 'keyword'], 'safe'],
            [['title', 'shareTitle', 'keyword'], 'string', 'length' => [1, 20]],
            [['shareContent'], 'string', 'length' => [1, 30]],
            [['description'], 'string', 'length' => [1, 50]],
            [['image', 'shareImage'], 'string'],
            [['title', 'image', 'description', 'shareTitle', 'shareImage', 'shareContent'], 'required', 'on' => ['create', 'update']],
            [['keyword'], 'required', 'on' => ['create']],
        ];
    }

    /**
     * 场景设置
     * @return array
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();//本行必填，不写的话就会报如上错误
        $scenarios['create'] = ['title', 'image', 'description', 'shareTitle', 'shareImage', 'shareContent', 'keyword'];
        $scenarios['edit'] = ['title', 'image', 'description', 'shareTitle', 'shareImage', 'shareContent'];
        return $scenarios;
    }

}