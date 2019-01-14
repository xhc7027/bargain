<?php

namespace app\controllers\filters;

use app\commons\SecurityUtil;
use app\commons\HttpUtil;
use app\commons\StringUtil;
use app\services\event\EventFacadeApi;
use Yii;
use yii\base\Behavior;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;

class ModulesAccessFilter extends Behavior
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
     * <p>请求前置拦截</p>
     * <p>
     * 向商城获取功能使用权限
     * </p>
     * @param \yii\base\ActionEvent $event
     * @return boolean
     */
    public function beforeAction($event)
    {
        $action = $event->action->id;
        if(!in_array($action, $this->actions)){
            return $event->isValid;
        }

        //判断功能是否免费
        if(!$this->functionIsFree()){
            //不免费就先判断用户是否有vip权限
            if( $event->isValid = $this->getVipLevel()){
                return $event->isValid;
            }
        }

        //判断用户是否在免费试用期间
        if (EventFacadeApi::getSupplierFreeUseTime(Yii::$app->session->get('userAuthInfo')['supplierId'])) {
            return $event->isValid;
        }
        //去商城获取权限，而且当该用户有权限的时候才放到session
        if ($event->isValid = $this->getMallModules()) {
            return $event->isValid;
        }

        if($action == 'index' && Yii::$app->session->get('check_buy_newbargain') != 1){
            Yii::$app->session->set('check_buy_newbargain', '0');
            $event->isValid = true;
        }

        return $event->isValid;
    }

    /**
     * 判断功能是否免费
     *
     * @return bool
     */
    private function functionIsFree()
    {
        return Yii::$app->params['isFreeButton'] ? true : false;
    }

    /**
     * 获取vip权限
     * @return bool
     */
    private function getVipLevel(){

        if($this->checkTime('vipDeadline')){
            Yii::$app->session->set('check_buy_newbargain', '1');
            return true;
        }
        $get = array(
            'timestamp' => time(),
            'state' => StringUtil::genRandomStr(),
            'apikey' => 839
        );//拼装get参数
        $post['supplierId'] = Yii::$app->session->get('userAuthInfo')['supplierId'];
        //1. 去爱豆子获取是否是平台vip
        $url = Yii::$app->params['serviceUrl']['idouziUrl'] . '/supplier/api/checkUserVipAuth?';
        $get['sign'] = (new SecurityUtil($get, Yii::$app->params['signKey']['iDouZiSignKey']))->generateSign();
        $url .= http_build_query($get);
        $resp = json_decode(HttpUtil::post($url, $post), true);
        //获取成功
        if (isset($resp['return_msg']['return_code']) && $resp['return_msg']['return_code'] === 'SUCCESS') {
            //等于2代表是平台vip 等于1代表是试用期内的用户
            if ($resp['return_msg']['return_msg']['user_role'] == 2 || $resp['return_msg']['return_msg']['user_role'] == 1) {
                Yii::$app->session->set('vipDeadline', $resp['return_msg']['return_msg']['valid_time']);
                Yii::$app->session->set('check_buy_newbargain', '1');
                return true;
            }
        }

        return false;

    }
    /**
     * 检查时间是否过期
     */
    private function checkTime($time){
        $expirTime = Yii::$app->session->get($time);
        return !is_null($expirTime) && $expirTime -time() > 0 ? true : false;
    }

    /**
     * 向商城请求
     * @param $ticket
     * @return bool
     */
    private function getMallModules()
    {

        if($this->checkTime('mallDeadline')){
            Yii::$app->session->set('check_buy_newbargain', '1');
            return true;
        }
        $get = array(
            'timestamp' => time(),
            'state' => StringUtil::genRandomStr(),
            'wxid' => Yii::$app->session->get('userAuthInfo')['supplierId']
        );//拼装get参数
        // 去商城获取功能权限
        $url = Yii::$app->params['serviceUrl']['MALL_URL'] . '/api/modules?';
        $get['sign'] = (new SecurityUtil($get, Yii::$app->params['signKey']['voteSignKey']))->generateSign();
        $url .= http_build_query($get);
        $resp = json_decode(HttpUtil::get($url), true);

        // 获取成功
        if (isset($resp['return_msg']['return_code']) && $resp['return_msg']['return_code'] === 'SUCCESS') {
            //authorization等于1代表有权限
            if ($resp['return_msg']['return_msg']['new_bargain']['authorization']) {
                isset($resp['return_msg']['return_msg']['new_bargain']['end_time']) ?
                Yii::$app->session->set('mallDeadline', $resp['return_msg']['return_msg']['new_bargain']['end_time']) :
                    Yii::$app->session->set('mallDeadline', 0);
                Yii::$app->session->set('check_buy_newbargain', '1');
                return true;
            }
            isset($resp['return_msg']['return_msg']['new_bargain']['gid'])
                ? Yii::$app->session->set('gid', $resp['return_msg']['return_msg']['new_bargain']['gid'])
                : Yii::$app->session->set('gid', -1);
        }
        return false;
    }

}