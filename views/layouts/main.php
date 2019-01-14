<?php

/* @var $this \yii\web\View */
/* @var $content string */

use app\assets\VueAsset;
use app\assets\AppAsset;
use app\assets\ElementsAsset;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\helpers\Html;
use yii\widgets\Breadcrumbs;

$mallUrl = Yii::$app->params['serviceUrl']['MALL_URL'];
$wxid = Yii::$app->session['userAuthInfo']['supplierId'];
$mainMenu = \app\commons\HttpUtil::mainMenu($wxid, $mallUrl);//从接口获取导航栏数据
$idouziUrl = Yii::$app->params['serviceUrl']['idouziUrl'];
ElementsAsset::register($this);
AppAsset::register($this);
VueAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="coupon-url" content="/supplier/get-coupon"/>
    <meta name="coupon-return-type" content="returnMsg"/>
    <meta name="cur-menu" content="我的应用"/>
    <?= Html::csrfMetaTags() ?>
    <title>爱豆子新微砍价</title>
    <link href="http://static-10006892.file.myqcloud.com/newIndex/common/iconfont2/iconfont-20180629.min.css" rel="stylesheet" type="text/css"/>
    <link href="http://static-10006892.file.myqcloud.com/newIndex/common/css/common-20180702.min.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="http://idouzivotenew-10006892.file.myqcloud.com/back/js/jquery-2.1.4.min.js"></script>
    <script src="http://static-10006892.file.myqcloud.com/public/js/idouzi-tools.min.js"></script>
    <script src="http://static-10006892.file.myqcloud.com/plugin/jquery-nicescroll/jquery.nicescroll-3.7.0.min.js"></script>
    <script src="http://static-10006892.file.myqcloud.com/newIndex/common/js/common-20180702.min.js"></script>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
    <div class="v-layout">
        <!-- 头部布局 -->
        <header class="layout-header">
            <div class="content">
                <!-- logo -->
                <a class="logo">
                    <img title="爱豆子管理后台"
                        src="http://static-10006892.file.myqcloud.com/newIndex/image/logo.png" 
                        alt="爱豆子管理后台">
                    <span class="name">管理后台</span>
                </a>

                <!-- 右侧功能菜单 -->
                <ul class="layout-user-items">
                    <!-- 用户名称 -->
                    <li class="item">
                        <a target="_blank" class="user-name">用户名</a>
                    </li>

                    <!-- 我的订单 -->
                    <li class="item coupon-link">
                        <a>我的订单</a>
                    </li>

                    <!-- 优惠券 -->
                    <!--<li class="item user-coupon">-->
                        <!--<a>优惠券</a>-->

                        <!--&lt;!&ndash; 优惠券模块 &ndash;&gt;-->
                        <!--<div class="user-coupon-list">-->
                            <!--&lt;!&ndash; 有优惠券 &ndash;&gt;-->
                            <!--<ul class="coupon-list">-->
                                <!---->
                            <!--</ul>-->

                            <!--&lt;!&ndash; 无优惠券 &ndash;&gt;-->
                            <!--<div class="no-data">-->
                                <!--<div class="tips">-->
                                    <!--<div class="title">【官方好福利】</div>-->
                                    <!--<div class="text">-->
                                        <!--通过豆子编辑器-->
                                        <!--<span class="color-orange">“存流量”</span>-->
                                        <!--模式-->
                                        <!--<br> 发送文章即可获得代金券哦~-->
                                    <!--</div>-->
                                    <!--<a class="editor-link" target="_blank">赶紧试试</a>-->
                                <!--</div>-->
                            <!--</div>-->
                        <!--</div>-->
                    <!--</li>-->

                    <!-- 退出 -->
                    <li class="item sign-out">
                        <a>退出</a>
                    </li>
                </ul>
            </div>
        </header>

        <main class="layout-main">
            <!-- 左侧公共菜单栏 -->
            <nav class="layout-nav">
                <div class="fix-wrap">
                    <ul class="items" id="menus">
                        <!-- <li class="item">
                            <!- 一级菜单 ->
                            <a class="link">
                                <i class="icon-logo icon iconfont icon-gongneng"></i>
                                <span class="name">一级菜单</span>
                                <i class="icon-arrow icon iconfont"></i>
                            </a>

                            <!- 二级菜单列表 ->
                            <ul class="sub-items">
                                <li class="sub-item">
                                    <a href="subMenu.link" class="sub-link">二级菜单</a>
                                </li>
                            </ul>
                        </li> -->
                    </ul>

                    <!-- 协议 -->
                    <a class="statement" href="/index.php?r=mobile/article/disclaimer&wxid=1693">爱豆子平台使用免责声明</a>

                    <!-- 二维码 -->
                    <div class="code">
                        <img src="http://static-10006892.file.myqcloud.com/official_website/img/aside-qrcode-min.png" title="爱豆子微信公众号" alt="爱豆子微信公众号">
                        <p>扫描微信二维码查看功能演示</p>
                    </div>
                </div>
            </nav>

            <!-- 页面主体插槽 -->
            <div id="main-wrap">
                <?php echo $content; ?>
            </div>
        </main>

        <!-- 右侧咨询模块 -->
        <aside class="layout-aside">
            <button type="button" class="show-hide-aside-btn">
                <i class="icon iconfont icon-kefu"></i>
                <span class="show-txt">收起</span>
                <span class="hide-txt">我要咨询</span>
                <i class="icon iconfont icon-xiangyouzhankai"></i>
            </button>
            <a class="contact-qq" target="_blank" href="">
                <i class="icon iconfont icon-QQ"></i>
                QQ咨询
            </a>
            <a class="contact" 
                target="_blank" 
                href="http://webim.qiao.baidu.com//im/index?siteid=2491305&amp;ucid=5649448">
                <i class="icon iconfont icon-kefu"></i>
                联系客服
            </a>
            <span class="tel">
                <i class="icon iconfont icon-dianhua"></i>
                <span class="tel-number">135654578</span>
            </span>
            <span class="qq-group">
                <i class="icon iconfont icon-QQ"></i>
                群: 564482792
                <p>群内不定期举办活动，参与活动后可有机会得到丰厚的功能使用权哦~</p>
            </span>
        </aside>
    </div>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
