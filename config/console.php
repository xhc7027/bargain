<?php
$config = require(__DIR__ . '/web.php');
$config['id'] = 'bargain-console';
$config['basePath'] = dirname(__DIR__);
$config['controllerNamespace'] = 'app\commands';
$config['components']['log']['targets'][0]['logFile'] = '@runtime/logs/console.log';
unset(
    $config['homeUrl'], $config['components']['request'], $config['components']['urlManager'],
    $config['components']['errorHandler']
);
return $config;
