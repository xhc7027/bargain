<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/21 0021
 * Time: 20:15
 */

namespace app\commons;

use Yii;

/**
 * 公用方法，每个项目都可以通过这个检测用的免登录权限
 * 保存，记录用户信息到cookie中，检测是否过期，并且从security中获取用户的tickets
 * @package Idouzi\Commons
 */
class RememberUserUtil
{
    /**
     * 将cookie中的userinfo字段进行检测拿出用户的信息
     * @param string $cookie
     * @return array|mixed
     * @internal param string $userInfo
     */
    public static function judgeUserInfo(string $cookie)
    {
        $data = explode('.', $cookie);
        $data = json_decode(base64_decode($data[1]), true);
        if ((time() - $data['lastLogin']) > 604800) {
            return [];
        }
        //将用户的信息拿到认证系统进行检测
        $resp = self::validateUserInfo($data);
        if(!$resp || $resp['return_code'] == 'FAIL')
        {
            return [];
        }
        return $resp;
    }

    /**
     * 到认证系统获取用户的tickets
     * @param array $userInfo
     * @return mixed
     */
    public static function validateUserInfo(array $userInfo)
    {
        try {
            $get = array(
                'timestamp' => time(),
                'state' => StringUtil::genRandomStr()
            );//拼装get参数
            //1. 把票据发送到认证系统认证，如果认证成功则维护登录状态，认证失败返回false
            $url = Yii::$app->params['serviceUrl']['ssoDomain'] . '/sso/get-ticket.html?';
            $get['sign'] = (new SecurityUtil($get, Yii::$app->params['signKey']['ssoSignKey']))->generateSign();
            $url .= http_build_query($get);
            $resp = json_decode(HttpUtil::post($url, $userInfo), true);
            return $resp['return_msg'];
        } catch (\Exception $e) {
            Yii::warning('validateUserInfo failed :' . $e->getMessage(), __METHOD__);
            return false;
        }

    }

    /**
     * 检测用户信息是否存在
     * @return mixed|null
     */
    public static function judgeUserInfoExist()
    {
        $data = Yii::$app->session->get('authorInfo');
        $sign = Yii::$app->params['tokenSign'];
        if (!isset($_COOKIE['authorInfo'])) {
            return null;
        }
        if ($data == $_COOKIE['authorInfo']) {
            return $data;
        } elseif ($data || $_COOKIE['authorInfo']) {
            $rememberMe = explode('.', $_COOKIE['authorInfo']);
            $rememberMe[0] . $rememberMe[1] . $sign == $rememberMe[2];
            $temple = hash("sha256", $rememberMe[0] . $rememberMe[1] . $sign);
            if ($temple == $rememberMe[2]) {
                return $_COOKIE['authorInfo'];
            }
        }
        return null;
    }
}