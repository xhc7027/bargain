<?php

namespace app\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AppAsset extends AssetBundle
{
    public $jsOptions = ['position' => View::POS_HEAD];
    public $basePath = '@webroot';
    // public $baseUrl = '@web';
    public $baseUrl = 'http://static-10006892.file.myqcloud.com/bargain/supplier/';
    public $css = [
        'css/common.min.css',
    ];
    public $js = [
        'js/public.min.js'
    ];
    public $depends = [

    ];
}
