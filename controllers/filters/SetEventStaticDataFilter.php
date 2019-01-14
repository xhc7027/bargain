<?php

namespace app\controllers\filters;

use app\models\Event;
use app\services\event\EventFacadeApi;
use Yii;
use yii\base\Behavior;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;

class SetEventStaticDataFilter extends Behavior
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
        if (!in_array($event->action->id, $this->actions)) {
            return $event->isValid;
        }

        //1. 链接不存在eventId参数
        if (!($eventId = Yii::$app->request->get('eventId'))) {
            throw new ForbiddenHttpException('访问出错啦，请检查链接是否正确吧');
        }

        //2.实例化活动模型
        $eventModel = Event::findOne($eventId);
        if (!$eventModel) {
            throw new ForbiddenHttpException('你访问了个亿次元空间，不存在这活动哦');
        }
        if (!$eventModel->setStaticEventData()) {
            $event->isValid = false;
        } else {
            //2.1 如果是首页则增加pv
            if ($event->action->id == 'index' && (Yii::$app->session->get('oauth_info') || Yii::$app->response->cookies->get('oauth_info'))) {
                $eventModel->addPv();
            }
        }

        return $event->isValid;
    }
}