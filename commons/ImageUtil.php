<?php

namespace app\commons;

use Tencentyun\ImageV2;
use Yii;

/**
 * 图片处理工具类
 * @package app\commons
 */
class ImageUtil
{
    /**
     * 新砍价图片存储在腾讯万象优图的空间名称
     */
    const BUCKET = 'bargain';

    /**
     * 把图片上传到腾讯万象优图
     * @param $sourceFilePath 在本地可直接访问路径
     * @param $fileName 自定义文件名称
     * @return string 返回图片浏览地址
     */
    public static function uploadTencentYunImg($sourceFilePath, $fileName)
    {
        $fileId = $fileName . '_' . md5(microtime() . mt_rand());
        $uploadRet = ImageV2::upload($sourceFilePath, self::BUCKET, $fileId);

        if (0 === $uploadRet['code']) {
            return $uploadRet['data']['downloadUrl'];
        }

        Yii::warning('把图片上传到腾讯万象优图失败，具体原因：' . $uploadRet['message'], __METHOD__);

        return null;
    }


}