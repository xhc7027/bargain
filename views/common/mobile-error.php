<?php

use yii\helpers\Html;

$this->title = $name;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title><?= Html::encode($this->title) ?></title>
    <style type="text/css">
        html {
            height: 100%;
        }

        body {
            display: table;
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
        }

        p {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }

        p:before {
            display: block;
            content: "!";
            width: 110px;
            height: 110px;
            line-height: 110px;
            margin: 0 auto 10px;
            color: #FFF;
            font-size: 100px;
            background-color: #F76260;
            -webkit-border-radius: 50%;
            border-radius: 50%;
        }
    </style>
</head>
<body>
<p><?= nl2br(Html::encode($message)) ?></p>
</body>
</html>
