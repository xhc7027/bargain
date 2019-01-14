<?php

namespace app\services;

use Yii;
use yii\web\Cookie;

/**
 * 处理cookie服务
 *
 * @package app\services
 */
class CookieService
{
    /**
     * 获取用户授权信息
     * @return array|string
     */
    public static function getOauthInfo()
    {
        return self::getCookie('oauth_info');
    }

    /**
     * 设置用户授权信息
     * @param array $value
     * @param int $expires
     */
    public static function setOauthInfo(array $value, int $expires = 0)
    {
        self::setCookie(['name' => 'oauth_info', 'value' => $value], $expires);
    }

    /**
     * 设置不同公众号里的openId
     * @param string $openId openId
     * @param string $appId 公众号id
     * @param int $expires 过期时间
     */
    public static function setOpenId(string $openId, string $appId, int $expires = 0)
    {
        self::setCookie(['name' => 'openId' . $appId, 'value' => $openId], $expires);
    }

    /**
     * 获取openId
     * @param string $appId 公众号id
     * @return array|string
     */
    public static function getOpenId(string $appId)
    {
        return self::getCookie('openId' . $appId);
    }

    /**
     * 获取cookie
     *
     * @param $key string 键
     * @return array|string
     */
    public static function getCookie(string $key)
    {
        $cookies = Yii::$app->request->getCookies();
        if (!isset($cookies[$key]->value)) {
            return [];
        }
        return $cookies[$key]->value;
    }

    /**
     * 设置cookie的数据
     *
     * @param $data array cookie里面的数据
     * @param $expires int 过期时间
     */
    public static function setCookie($data, int $expires = 0)
    {
        $data['expire'] = $expires === 0 ? 0 : time() + $expires;
        $cookie = new Cookie($data);
        Yii::$app->response->cookies->add($cookie);
    }
}