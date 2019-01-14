<?php

namespace app\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * Vue
 */
class ElementsAsset extends AssetBundle
{
    public $jsOptions = ['position' => View::POS_BEGIN];
    public $basePath = '@webroot';
    public $baseUrl = 'http://static-10006892.file.myqcloud.com/plugin/elements/1.1.6/';

    public $css = [
        'elements.css',
    ];

    public $js = [
        'elements.js',
    ];
}
