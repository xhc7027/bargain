<?php

$mongodb = require(__DIR__ . '/db.php');

//需要动态获取的配置
$dynamicConfig = [
    'id' => 'new-bargain',//项目编号标识
    'basePath' => dirname(__DIR__),
];

//需要静态获取的配置
$staticConfig = Yaconf::get($dynamicConfig['id']);


$commonConfig = Yaconf::get('common');
if (!$commonConfig) {
    throw new Exception('不能加载配置文件:common');
}

if(isset($staticConfig['params']['constant']) && isset($commonConfig['constant'])){
    foreach ($staticConfig['params']['constant'] as $key => $param){
        $staticConfig['params']['constant'][$key] = isset($commonConfig['constant'][$key]) ?
            array_merge($commonConfig['constant'][$key], $param) : $param;
    }

    //如果common有的，则去本系统看是否有，有则合并
    foreach ($commonConfig['constant'] as $key => $param){
        $staticConfig['params']['constant'][$key] = isset($staticConfig['params']['constant'][$key]) ?
            array_merge($commonConfig['constant'][$key], $staticConfig['params']['constant'][$key]) :
            $commonConfig['constant'][$key];
    }
}
$staticConfig['params'] = array_merge($commonConfig, $staticConfig['params']);

//默认活动开始时间和结束时间，相差七天
$staticConfig['params']['defaultEventConf']['startTime'] = time();
$staticConfig['params']['defaultEventConf']['endTime'] = time() + 24 * 60 * 60 * 7;
unset($staticConfig['mongodb']);
$staticConfig['components']['mongodb'] = $mongodb;


return array_merge($dynamicConfig, $staticConfig);

