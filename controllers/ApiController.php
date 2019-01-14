<?php

namespace app\controllers;

use app\commons\ForGrowingUtil;
use app\commons\SecurityUtil;
use app\models\Bargain;
use app\models\EventStatistics;
use app\models\RespMsg;
use app\services\data\DataFacadeApi;
use app\services\event\EventFacadeApi;
use app\services\growing\GrowingService;
use app\services\handle\HandleApi;
use app\services\bargain\BargainStrategyApi;
use app\services\weixin\WeiXinService;
use yii\base\Exception;
use yii\web\Controller;
use Yii;
use yii\web\Cookie;
use app\models\Event;

/**
 * Created by PhpStorm.
 * User: 关国亮
 * Date: 2017/3/9 0009
 * Time: 下午 4:28
 */
class ApiController extends Controller
{

    public $enableCsrfValidation = false;

    /**
     * 接收网页授权的回调数据，并将数据存在cookie中
     * <h>回调数据包括</h>
     * <li>openid</li>
     * <li>access_token</li>
     * <li>refresh_token</li>
     *
     * @return string
     */
    public function actionGetAuthData()
    {
        //1、接收代理平台的授权回调数据。
        $openId = Yii::$app->request->getQueryParam('openid');//用户OPENID
        if (empty($openId)) {
            return "参数错误，网页授权失败，请重试~";
        }
        try {
            //3、直接获取下用户信息并存到session中
            (new WeiXinService())->getUserDataFromApi($openId, WeiXinService::getAppIdInSession());
            //4、授权后跳转到最终的业务界面
            $this->redirect(Yii::$app->session['auth_redirect_url']);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

    }

    /**
     * 获取砍价商品信息的对外接口
     *
     * @return RespMsg
     */
    public function actionGetBargainInfo()
    {
        if (!(new SecurityUtil(Yii::$app->request->get(), Yii::$app->params['signKey']['bargainSignKey']))->signVerification()) {
            return new RespMsg(['return_code' => RespMsg::FAIL, 'return_msg' => '签名失败']);
        }
        $post = Yii::$app->request->post();
        if (!isset($post['openId']) || !isset($post['eventId'])) {
            return new RespMsg(['return_code' => RespMsg::FAIL, 'return_msg' => '非法参数']);
        }
        return HandleApi::getMallNeedInfo($post['eventId'], $post['openId']);
    }

    /**
     * 更新商品状态
     *
     * @return RespMsg
     */
    public function actionUpdateShopOrder()
    {
        $respMsg = new RespMsg();
        $bargainSignKey = Yii::$app->params['signKey']['bargainSignKey'];
        if (!(new SecurityUtil(Yii::$app->request->get(), $bargainSignKey))->signVerification()) {
            $respMsg->return_msg = '签名错误';
            $respMsg->return_code = RespMsg::FAIL;
            return $respMsg;
        }
        $post = Yii::$app->request->post();
        //创建和更新订单都必须的参数
        if (!isset($post['orderId']) || !isset($post['status']) || !isset($post['do'])) {
            $respMsg->return_msg = '非法参数';
            $respMsg->return_code = RespMsg::FAIL;
            return $respMsg;
        }
        if (!in_array($post['do'], ['create', 'update'])) {
            $respMsg->return_msg = '参数错误';
            $respMsg->return_code = RespMsg::FAIL;
            return $respMsg;
        }
        //创建订单时需要openId和bargainId
        if ($post['do'] == 'create') {
            if (!isset($post['openId']) || !isset($post['bargainId']) || !isset($post['address'])) {
                $respMsg->return_msg = '参数错误';
                $respMsg->return_code = RespMsg::FAIL;
                return $respMsg;
            }
        }
        $respMsg = (new HandleApi)->updateShopInfo($post);
        return $respMsg;
    }

    /**
     * 用于关键字给消息平台回复
     *
     * @return RespMsg
     */
    public function actionBargainReply()
    {
        $security = new SecurityUtil(Yii::$app->request->post(), Yii::$app->params['signKey']['bargainSignKey']);
        $respMsg = new RespMsg();
        //签名校验
        if (!$security->signVerification()) {
            $respMsg->return_msg = '签名失败';
            $respMsg->return_code = RespMsg::FAIL;
            return $respMsg;
        }
        $post = Yii::$app->request->post();
        //参数校验
        if (!$post['eventId']) {
            $respMsg->return_msg = '非法参数';
            $respMsg->return_code = RespMsg::FAIL;
            return $respMsg;
        }
        try {
            return WeiXinService::getReplyInfo($post['eventId']);
        } catch (\Exception $e) {
            $respMsg->return_msg = $e->getMessage();
            $respMsg->return_code = RespMsg::FAIL;
        }
        return $respMsg;
    }

    //漏斗数据
    public function actionGrowingVote()
    {
        $get = Yii::$app->request->get();
        unset($get['r']);
        //如果是外部请求
        if (Yii::$app->request->get('outer') == 1) {
            $res = (new SecurityUtil($get, Yii::$app->params['signKey']['voteSignKey']))->signVerification();
            if ($res == false) {//签名认证
                echo json_encode(array('status' => 0, 'data' => ''));
                return;
            }
        }
        $wxId = Yii::$app->request->get('wxid');
        $session = Yii::$app->session->get('growingIo_' . $wxId);
        $token = (new GrowingService())->authToken('id:' . $wxId);
        $postUrl = 'https://data.growingio.com/saas/9c6fead577bbabb2/user?auth=' . $token;
        $headers = array('Access-Token:2515a7ffc4db4c10bd36784d02f7383b', 'Content-Type:application/json');
        //判断session是否有数据
        if ($session && ($postData = Yii::$app->session->get('growingIo_api_' . $wxId))) {
            GrowingService::httpToGrowing($postUrl, 'POST', json_encode($postData), $headers);
            echo json_encode(array('status' => 100, 'data' => $session));
            return;
        }
        $data = [];
        //新砍价的自身请求
        if (Yii::$app->request->get('outer', 0) == 0) {
            $getRequest = array('wxid' => $wxId, 'timestamp' => time(), 'outer' => 1);
            //爱豆子 参数签名
            $getRequest['sign'] = ((new SecurityUtil($getRequest, Yii::$app->params['signKey']['voteSignKey'])))->generateSign();

            //获取请求链接
            $idouziUrl = Yii::$app->params['serviceUrl']['idouziUrl'] . "/index.php?r=supplier/api/growingIdouzi&apikey=839&";
            $mallUrl = Yii::$app->params['serviceUrl']['MALL_URL'] . '/api/growing-mall?';
            $voteUrl = Yii::$app->params['serviceUrl']['voteUrl'] . '/api/growing-vote?';
            //分别获取数据
            $idouziData = GrowingService::getServiceGrowing($idouziUrl, $wxId);
            //$mallData = GrowingService::getServiceGrowing($mallUrl, $wxId);
            //$voteData = GrowingService::getServiceGrowing($voteUrl, $wxId);
            //数据组合一起
            $data = array_merge($idouziData);

        }
        GrowingService::httpToGrowing(
            $postUrl,
            'POST',
            json_encode($postData = GrowingService::returnGrowingData($data)),
            $headers
        );
        Yii::$app->session->set('growingIo_' . $wxId, $data);
        Yii::$app->session->set('growingIo_api_' . $wxId, $postData);
        echo json_encode(array('status' => 100, 'data' => $data));
        return;
    }

    /**
     *  获取砍价活动信息（活动pv和参赛人数）
     *
     * @return RespMsg
     */

    public function actionGetBarginInfo()
    {
        $respMsg = new RespMsg();
        try {

            (new SecurityUtil(Yii::$app->request->post(), Yii::$app->params['signKey']['bargainSignKey']))
                ->signVerification();//签名接口认证

        } catch (Exception $e) {
            $respMsg->return_code = RespMsg::FAIL;
            $respMsg->return_msg = $e->getMessage();
            return $respMsg;
        }
        $wxidArr = json_decode(Yii::$app->request->post('ids'), true);

        if (!is_array($wxidArr) || count($wxidArr) < 1) {//不是数组或者数组为空
            $respMsg->return_code = RespMsg::FAIL;
            $respMsg->return_msg = '输入值不能为空';
            return $respMsg;
        }
        foreach ($wxidArr as &$v) {
            $v = addslashes($v);//数据过滤
        }
        $eventQueryResult = (new Event)->find()
            ->select(['_id', 'name', 'createTime', 'startTime', 'endTime', 'participants', 'pv', 'advancedSetting.keyword'])
            ->where(['_id' => $wxidArr])->asArray()->all();
        $eventStatistics = new EventStatistics();
        foreach ($eventQueryResult as &$record) {
            $record['_id'] = $record['_id']->__tostring();//把对象转变为字符
            $record['source'] = $eventStatistics->find()->where(['eventId' => $record['_id']])->asArray()->all();
            $record['count'] = 0;
            $record['count_uv'] = 0;
        }
        //组装数据
        $eventQueryResult = BargainStrategyApi::reSetData($eventQueryResult);
        $respMsg->return_msg = json_encode($eventQueryResult);
        return $respMsg;
    }

    /**
     * 是否记录流量
     */
    public function actionCheckSupplierPattern()
    {
        $respMsg = new RespMsg();
        try {
            if (!$eventId = Yii::$app->request->post('eventId')) {
                $respMsg->return_code = RespMsg::FAIL;
                $respMsg->return_msg = '参数错误';
                return $respMsg;
            }
            (new SecurityUtil(Yii::$app->request->get(), Yii::$app->params['signKey']['bargainSignKey']))
                ->signVerification();//签名接口认证

            $respMsg->return_msg = EventFacadeApi::checkIsChargePattern($eventId);
        } catch (Exception $e) {
            $respMsg->return_code = RespMsg::FAIL;
            $respMsg->return_msg = $e->getMessage();
        }

        return $respMsg;
    }

}