<?php

namespace app\services\image;

use app\commons\ImageUtil;

class ImageService
{

    /**
     * 上传图片到腾讯优图
     * @param $file  在本地可直接访问路径
     * @return string
     * @throws \yii\base\UserException
     */
    public static function uploadImage(array $image)
    {
        $path = ImageUtil::uploadTencentYunImg($image['tmp_name'], 'newbargain');
        if ($path == null) {
            $path = ImageUtil::uploadTencentYunImg($image['tmp_name'], 'newbargain');
        }

        return $path;
    }

}