<?php

namespace app\controllers\filters;

use app\models\Event;
use app\models\Bargain;
use Yii;
use yii\base\Behavior;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;

/**
 * 砍价后台在进行兑奖，删除或者关闭活动的时候进行数据过滤处理
 * 主要是判断bargain是否存在和活动event是否存在
 * Class EventInfoFilter
 * @package app\controllers\filters
 */
class EventInfoFilter extends Behavior
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
     * 判断是否存在或者是否有权限
     * @param \yii\base\ActionEvent $event
     * @return bool
     * @throws ForbiddenHttpException
     */
    public function beforeAction($event)
    {
        //判断进入的action是否在过滤的action中，没有则不需要验证
        if (!in_array($event->action->id, $this->actions)) {
            return $event->isValid;
        }

        //判断是否是进入兑奖接口
        if ($event->action->id == 'cash-prize') {
            //1. 链接不存在bargainId参数
            if (!($bargainId = Yii::$app->request->get('bargainId'))) {
                throw new ForbiddenHttpException('访问出错啦，请检查链接是否正确吧');
            }
            $bargain = Bargain::find()->select(['eventId'])->where(['_id' => $bargainId])->one();
            //判断是否参加了该活动
            if (empty($bargain)) {
                throw new ForbiddenHttpException('访问出错啦，请检查链接是否正确吧');
            }
            $eventId = $bargain['eventId'];
        } else {
            //1. 链接不存在eventId参数
            if (!($eventId = Yii::$app->request->get('eventId'))) {
                throw new ForbiddenHttpException('访问出错啦，请检查链接是否正确吧');
            }
        }

        $eventInfo = Event::find()->select(['founder.id'])->where(['_id' => $eventId])->one();
        //判断该活动是否存在
        if (empty($eventInfo)) {
            $event->isValid = false;
        }
        //判断是否属于该商家的
        if (Yii::$app->session->get('userAuthInfo')['supplierId'] != $eventInfo['founder']['id']) {
            $event->isValid = false;
        }
        return $event->isValid;
    }
}