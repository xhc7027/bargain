<?php

namespace app\services\weixin;

use app\commons\HttpUtil;
use app\commons\SecurityUtil;
use app\commons\StringUtil;
use app\exceptions\SystemException;
use app\models\Bargain;
use app\models\Event;
use app\models\RespMsg;
use app\services\CookieService;
use Idouzi\Commons\ConstantUtil;
use Yii;
use yii\base\Exception;
use yii\web\Cookie;
use yii\web\ForbiddenHttpException;

/**
 * Created by PhpStorm.
 * User: 关国亮
 * Date: 2017/3/9 0009
 * Time: 下午 3:30
 */
class WeiXinService
{

    /**
     * @var integer 商家id
     */
    public $wxId;

    /**
     * @var array $oauth_info 微信跳转授权信息
     */
    public $oauth_info;


    public function getUserInfoFromApiService(string $appId, $eventId)
    {
        $from_url = Yii::$app->request->hostInfo . Yii::$app->request->getUrl();
        Yii::$app->session['auth_redirect_url'] = $from_url;//最终跳转的业务地址
        //1、先判断session中是否有用户数据
        if (isset(Yii::$app->session['oauth_info']) && is_array(Yii::$app->session['oauth_info'])) {
            $this->oauth_info = Yii::$app->session['oauth_info'];
            return;
        }

        $this->goToApiForWebAuthorize(
            $appId,
            Yii::$app->request->hostInfo . '/api/get-auth-data?eventId=' . $eventId,
            'snsapi_userinfo'
        );
    }

    /**
     * 去代理平台发起网页授权
     * @param $appId string 公众号id
     * @param $redirectUri string 返回的链接
     * @param string $scope 授权类型
     * @return string|array
     * @throws SystemException
     */
    private function goToApiForWebAuthorize(string $appId, string $redirectUri, $scope = 'snsapi_base')
    {
        //向代理平台获取信息
        $url = Yii::$app->params['serviceUrl']['weiXinApiDomain'] . '/facade/to-get-web-token?';
        $get = [
            'appId' => $appId,
            'redirectUri' => $redirectUri,
            'scope' => $scope,
            'openId' => CookieService::getOpenId($appId)
        ];
        return Yii::$app->response->redirect($url . http_build_query($get));
    }

    /**
     * 在session设置 appid
     * @param string $appId
     */
    public static function setAppIdInSession(string $appId)
    {
        Yii::$app->session->set(ConstantUtil::getSessionName('appId'), $appId);
    }

    /**
     * 在session获取appid
     */
    public static function getAppIdInSession()
    {
        return Yii::$app->session->get(ConstantUtil::getSessionName('appId'));
    }

    /**
     * 从代理平台，获取用户基本信息，成功则写入session http://trac.idouzi.com/trac/idouzi/ticket/411
     * @param $openId
     * @param $accessToken
     * @throws Exception
     */
    public function getUserDataFromApi($openId, $appId)
    {
        $params = array('timestamp' => time(), 'openId' => $openId, 'appId' => $appId);
        $params['sign'] = (new SecurityUtil($params, Yii::$app->params['signKey']['apiSignKey']))->generateSign();
        $url = Yii::$app->params['serviceUrl']['weiXinApiDomain'] . '/facade/get-web-user-info?'
            . http_build_query($params);
        $resp = json_decode(HttpUtil::get($url), true);

        if (isset($resp['return_msg']['return_code']) && $resp['return_msg']['return_code'] == 'SUCCESS') {
            Yii::$app->session['oauth_info'] = $resp['return_msg']['return_msg'];
            //把信息存到cookie
            $oauth_info = Yii::$app->session['oauth_info'];
            $oauth_info['sign'] = md5(
                $resp['return_msg']['return_msg']['openid']
                . '&'
                . Yii::$app->params['signKey']['bargainSignKey']
            );
            CookieService::setOauthInfo($oauth_info, 60 * 60 * 24 * 3);

            //openId单独存起来
            CookieService::setOpenId($resp['return_msg']['return_msg']['openid'], $appId, 60 * 60 * 24 * 30);
        } else {
            Yii::warning('get user data error! params=' . json_encode($params) . ',resp=' . json_encode($resp), __METHOD__);
            throw new Exception('登录授权失败');
        }

    }


    /**
     * @param int $wxId
     * @param string $fromUrl
     * @param string $params
     * @return RespMsg
     */
    public static function getJsSdkConf($wxId, string $fromUrl = '', $params)
    {
        $apiUrl = Yii::$app->params['serviceUrl']['weiXinApiDomain'];
        $get = ['type' => 'wxId', 'url' => $fromUrl, 'appid' => $wxId, 'timestamp' => time(), 'state' => StringUtil::genRandomStr()];
        $get['sign'] = (new SecurityUtil($get, Yii::$app->params['signKey']['apiSignKey']))->generateSign();
        $url = $apiUrl . '/facade/web-page?' . http_build_query($get);
        $res = json_decode(HttpUtil::get($url), true);
        if ($res['return_code'] == RespMsg::FAIL) {
            return new RespMsg(['return_code' => RespMsg::FAIL, 'return_msg' => '请求出错，请重试' . json_encode($res)]);
        }
        if ($res['return_msg']['return_code'] == RespMsg::FAIL) {
            Yii::warning('get jssdk failed :' . json_encode($res['return_msg']['return_msg']), __METHOD__);
            return new RespMsg(['return_code' => RespMsg::FAIL, 'return_msg' => '获取jssdk失败,请重试']);
        }
        $res['return_msg']['return_msg']['shareUrl'] = $fromUrl . '&' . $params;
        return new RespMsg(['return_msg' => $res['return_msg']['return_msg']]);
    }

    /**
     * 将获取的jsSdk数据组装成前端需要的格式
     * @param $wxId
     * @param $fromUrl
     * @param $params
     * @return RespMsg
     */
    public static function returnJsSdkConf($wxId, $fromUrl, $params)
    {
        $respMsg = new RespMsg();
        //1.向代理平台获取jsSdk配置
        $jsSdkConf = json_decode(WeiXinService::getJsSdkConf($wxId, $fromUrl, $params), true);
        if ($jsSdkConf['return_code'] == RespMsg::FAIL) {
            $respMsg->return_msg = $jsSdkConf['return_msg'];
            $respMsg->return_code = $jsSdkConf['return_code'];
        } elseif (isset($jsSdkConf['return_msg']['return_code']) && $jsSdkConf['return_msg']['return_code'] == RespMsg::FAIL) {
            $respMsg->return_msg = '获取数据失败';
            $respMsg->return_code = RespMsg::FAIL;
            Yii::warning('获取jsSdk失败：' . json_encode($jsSdkConf), __METHOD__);
        } else {
            //2.组装jsSdk数据
            $data['appId'] = $jsSdkConf['return_msg']['appId'];
            $data['nonceStr'] = $jsSdkConf['return_msg']['nonceStr'];
            $data['signature'] = $jsSdkConf['return_msg']['signature'];
            $data['timestamp'] = $jsSdkConf['return_msg']['timestamp'];
            $data['shareUrl'] = $jsSdkConf['return_msg']['shareUrl'];
            $respMsg->return_msg = $data;
        }
        return $respMsg;
    }

    /**
     * 报名保存后返回jsSdk与参加人的微信信息
     * @return RespMsg
     * @throws ForbiddenHttpException
     */
    public static function returnInfoAfterJoinSave(Bargain $bargain)
    {
        $respMsg = new RespMsg();
        $str = 'bargainId=' . $bargain->_id->__toString();
        $wxId = Yii::$app->session->get('event_' . Yii::$app->session->get('eventId'))['founder']['id'];
        //获取需要分享的请求链接，该值在获取活动基本信息时候保存
        if (!($fromUrl = Yii::$app->session->get('fromUrl'))) {
            throw new ForbiddenHttpException('访问出错啦，请重新刷新页面');
        }
        //去获取新的jsSdk
        $jsSdk = WeiXinService::returnJsSdkConf($wxId, $fromUrl, $str);
        if ($jsSdk->return_code == RespMsg::FAIL) {
            $respMsg->return_code = RespMsg::FAIL;
            $respMsg->return_msg = '获取jsSdk失败';
        } else {//3.1 获取jsSdk配置成功
            $respMsg->return_msg['jsSdk'] = $jsSdk->return_msg;
            $respMsg->return_msg['wxInfo'] = ['headImg' => $bargain->headImg, 'nickName' => $bargain->nickName];
        }
        return $respMsg;
    }

    /**
     * 获取关键字回复信息
     * @param string $eventId
     */
    public static function getReplyInfo(string $eventId)
    {
        $respMsg = new RespMsg();
        $eventData = Event::find()->select([
            'advancedSetting.title',
            'advancedSetting.description',
            'advancedSetting.image',
        ])->where(['_id' => $eventId, 'isDeleted' => 0])->asArray()->scalar();
        //假如不存在该活动
        if (!$eventData) {
            throw new SystemException('该活动不存在');
        }
        //组装数据
        $replyData = [
            'title' => $eventData['title'],
            'description' => $eventData['description'],
            'image' => $eventData['image'],
        ];
        $respMsg->return_msg = $replyData;
        return $respMsg;
    }
}
