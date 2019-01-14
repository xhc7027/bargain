<?php
namespace app\commons;

use Yii;
use yii\base\Exception;

/**
 * Created by PhpStorm.
 * User: 关国亮
 * Date: 2017/3/8 0008
 * Time: 下午 4:38
 */
class FunctionUtil
{

    /**
     * 正则匹配二级域名
     * @param $url
     * @return array|boolean
     */
    public static function getSLDMatches($url)
    {
        if (preg_match("/^(http:\/\/|https:\/\/)?([^\/:]+\.)?([^\/:]+\.[^\/:]+\..+)$/", $url, $matchs)) {
            return $matchs;
        } else {
            return false;
        }
    }

    /**
     * 获取对应域名的三级链接地址
     * @param  $host
     * @param  $wxid
     * @param  $url
     * @return boolean|string
     */
    public static function getTLD($host, $wxid, $url = "", $hostupdate = "")
    {
        $matchs = self::getSLDMatches($host);
        if (!empty($hostupdate)) {
            $matchs[3] = $hostupdate . "." . substr($matchs[3], (strpos($matchs[3], ".") + 1));
        }
        $redirct_url = $matchs[1] . $wxid . "." . $matchs[3];
        if ($host == $redirct_url) {
            return false;
        } else {
            return $redirct_url . $url;//需跳转地址
        }
    }


}