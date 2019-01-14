<?php
namespace app\services;

use Yii;

/**
 * session服务模块
 */
class SessionService
{
    /**
     * 设置新商城的状态
     *
     * @param $status integer 新商城状态
     */
    public static function setNewMallStatus(int $status)
    {
        self::setSession('newMallStatus', $status);
    }

    /**
     * 获取新商城状态
     */
    public static function getNewMallStatus()
    {
        return self::getSession('newMallStatus');
    }

    /**
     * 获取session数据
     *
     * @param string $queryKey 获取的key值
     * @param string $specialString 其他特殊的动态变化的拼接字符
     * @return mixed
     */
    private static function getSession($queryKey, $specialString = '')
    {
        $key = Yii::$app->params['constant']['session'][$queryKey];
        if ($specialString) {
            return Yii::$app->session->get($key . $specialString);
        }
        return Yii::$app->session->get($key);
    }

    /**
     * 设置session数据
     *
     * @param $setKey string 存入session的key
     * @param $val string|array|null  存入session的值
     * @param string $specialString 其他特殊的动态变化的拼接字符
     */
    private static function setSession($setKey, $val, $specialString = '')
    {
        $key = Yii::$app->params['constant']['session'][$setKey];
        if ($specialString) {
            Yii::$app->session->set($key . $specialString, $val);
        } else {
            Yii::$app->session->set($key, $val);
        }
    }

    /**
     * 清空session
     */
    public static function destroySession()
    {
        Yii::$app->session->destroy();
    }
}