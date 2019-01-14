<?php

namespace app\models;

use app\services\Mobile\MobilePrivate;
use yii\base\Model;
use Yii;

/**
 * 砍价联系人模型
 *
 * @package app\models
 */
class BargainContact extends Model
{


    /**
     * 砍价联系人这个比较特殊，需要去custom_field表去读取数据
     *
     * @param array $names
     * @return array
     */
    public static function nameTransferId(array $names)
    {
        return CustomField::getCustomFieldIdByNames($names);
    }

}