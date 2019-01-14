<?php

namespace app\commons;

/**
 * 公共工具方法
 *
 * @package app\services\utils
 */
class StringUtil
{
    /**
     * 随机生成指定位（默认16位）的字符串
     *
     * @param int $length 要生成字符的个数，默认16个
     * @return string 生成的字符串
     */
    public static function genRandomStr($length = 16)
    {
        $str = '';
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol) - 1;
        for ($i = 0; $i < $length; $i++) {
            $str .= $strPol[mt_rand(0, $max)];
        }
        return $str;
    }

    /**
     * 手机号的判断条件 兼容到香港乃至全球地区
     * @param $num
     * @return bool
     */
    public static function is_phone_num_global($num)
    {
        $match = "/^13[0-9]{1}[0-9]{8}$|17[0-9]{1}[0-9]{8}$|15[0-9]{1}[0-9]{8}$|18[0-9]{1}[0-9]{8}|[1-9]{1}[0-9]{7,9}$/";
        if (preg_match($match, $num)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * emoji表情处理
     *
     * @param $value string 字符串
     * @return mixed
     */
    public static function dealEmoji($value)
    {
        $value = preg_replace("/\\\u[ed][0-9a-f]{3}\\\u[ed][0-9a-f]{3}/", "*", json_encode($value));//替换成*
        return json_decode($value);
    }
}