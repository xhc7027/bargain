<?php

namespace app\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * Vue
 */
class VueAsset extends AssetBundle
{
    public $jsOptions = ['position' => View::POS_HEAD];
    public $basePath = '@webroot';
    // public $baseUrl = '@web';
    public $baseUrl = 'http://static-10006892.file.myqcloud.com/plugin/';

    public $js = [
        'vue/vue-2.1.10.min.js',
        'vue-resource/vue-resource-1.2.1.min.js'
    ];

}
