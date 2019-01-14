<?php

namespace app\controllers\filters;

use app\services\event\EventFacadeApi;
use Yii;
use yii\base\Behavior;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;

class GetEventStaticDataFilter extends Behavior
{
    /**
     * @var array 定义要处理的动作
     */
    public $actions;

    public function events()
    {
        return [Controller::EVENT_BEFORE_ACTION => 'beforeAction'];
    }

    /**
     * @param \yii\base\ActionEvent $event
     * @return bool
     * @throws ForbiddenHttpException
     */
    public function beforeAction($event)
    {
        //1. 获取活动的id
        if (!($eventId = Yii::$app->session->get('eventId'))) {
            throw new ForbiddenHttpException('访问出错啦，再刷新页面吧');
        }
        //2. 获取活动的静态数据
        if (!Yii::$app->session->get('event_' . $eventId)) {
            throw new ForbiddenHttpException('访问出错啦，再刷新页面吧');
        }

        return $event->isValid;
    }
}