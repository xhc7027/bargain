<?php

namespace app\controllers\filters;

use app\models\Event;
use app\services\CookieService;
use app\services\event\EventFacadeApi;
use app\services\weixin\WeiXinService;
use Yii;
use yii\base\Behavior;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;

/**
 * @package app\controllers\filters
 */
class GetUserInfoFilter extends Behavior
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
        //1. 判断session是否存在
        if (Yii::$app->session->get('oauth_info')) {
            return $event->isValid;
        }
        if ($oauth_info = CookieService::getOauthInfo()) {
            if (isset($oauth_info['sign']) && isset($oauth_info['openid'])) {
                $sign = $oauth_info['sign'];
                $openId = $oauth_info['openid'];
                if ($sign == md5($openId . '&' . Yii::$app->params['signKey']['bargainSignKey'])) {
                    Yii::$app->session->set('oauth_info', $oauth_info);
                    return $event->isValid;
                }
            }
        }
        //1.1 没有就需要用户授权，从代理平台获取
        $eventId = Yii::$app->request->get('eventId');
        $eventModel = Event::findOne($eventId);
        if (!$eventModel) {
            throw new ForbiddenHttpException('访问出错啦，再刷刷吧');
        }

        WeiXinService::setAppIdInSession($eventModel->founder['appId']);
        //1.2 直接调用代理平台接口
        (new WeiXinService())->getUserInfoFromApiService($eventModel->founder['appId'], $eventId);
        if (!Yii::$app->session->get('oauth_info')) {
            $event->isValid = false;
        }

        return $event->isValid;
    }
}