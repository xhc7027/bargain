<?php

namespace app\controllers\filters;

use app\commons\SubmitDataUtil;
use yii\base\Behavior;
use yii\base\Controller;

/**
 * 对用户提交的get和post进行xss和sql过滤
 * Class RequestDataFilter
 * @package app\controllers\filters
 */
class RequestDataFilter extends Behavior
{
    public function events()
    {
        return [Controller::EVENT_BEFORE_ACTION => 'beforeAction'];
    }

    public function beforeAction($event)
    {
        SubmitDataUtil::filterParam();//用户提交的数据过滤
        return $event->isValid;
    }
}