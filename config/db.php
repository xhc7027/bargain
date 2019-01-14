<?php

return [
    'class' => '\yii\mongodb\Connection',
    'dsn' => 'mongodb://' . Yaconf::get('new-bargain.mongodb.user') . ':' . Yaconf::get('new-bargain.mongodb.password')
        . '@' . Yaconf::get('new-bargain.mongodb.host') . ':' . Yaconf::get('new-bargain.mongodb.port') .
        '/' . Yaconf::get('new-bargain.mongodb.databaseName') . '?authSource=admin&readPreference=secondaryPreferred',
];
