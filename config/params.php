<?php

return [
    'defaultId' => '575e34ef8d5d6e723640b251',

    'supplierBargainAction' => ['create', 'edit', 'copy'],
    'mobileBargainAction' => ['info', 'event'],//info代表商品信息，self代表自己砍价的页面,share代表分享出去的页面
    'signKey' => [
        'iDouZiSignKey' => '1USyZ9Adxx8lI58hsikLbnBZpMEGn4gA',//爱豆子跨服务接口安全认证key
        'apiSignKey' => '1USyZ9Adxx8lI58hsikLbnBZpMEGn4gA',//代理平台跨服务接口安全认证key
        'mallSignKey' => '1USyZ9Adxx8lI58hsikLbnBZpMEGn41A',//商城系统跨服务接口安全认证key
        'voteSignKey' => '1USyZ9Adxx8lI58hsikLbnBZpMEGn4gA',//微投票安全认证key
        'bargainSignKey' => '1USyZ9Adxx8lI58hsikLbnBZpMEGn4qA',//砍价安全认证key
        'ssoSignKey' => 'xah9hohth8eiChah2hi6quahsae4eeso',//集中身份认证系统接口安全认证key
    ],
    'serviceUrl' => [
        'idouziUrl' => 'http://new.idouzi.com',
        'idouziWebUrl' => 'http://web2.idouzi.com',
        'weiXinApiDomain' => 'http://weixinapi2.idouzi.com',//代理平台服务域名
        'MALL_URL' => 'http://mall2.idouzi.com',//商城服务域名
        'bargainDomain' => 'http://bargain-dev.idouzi.com',//砍价服务域名
        'ssoDomain' => 'https://security-dev.idouzi.com',//集中身份认证系统域名
        'voteUrl' => 'http://vote2.idouzi.com',//微投票服务地址
        'sendGoodsUrl' => 'http://new.idouzi.com/supplier/shop/orderList?action=shop_shopshow',//去发货的链接
    ],
    'shopStatus' => [
        '0' => '未知状态',
        '1' => '待付款',
        '2' => '货到付款',
        '3' => '已购买',
        '4' => '已关闭',
        '5' => '已发货',
        '6' => '已完成',
        '7' => '订单过期',
        '8' => '已退款',
        '9' => '商家已删除',
        '10' => '退款中',
        '11' => '退款中',
    ],
    'needToUpdateStatus' => [1 => 'minus', 4 => 'add', 7 => 'add', 8 => 'add'],//需要去判断的订单状态
    'perMinOrderCheckLimit' => 1000,
    'accessFilter' => [
        'AdminAccessFilter' => 'http://new.idouzi.com/index.php?r=houtai/user/logout',
        'SupplierAccessFilter' => 'http://new.idouzi.com/supplier/user/logout',
        'SupplierAccessLogin' => 'http://new.idouzi.com/supplier/user/login',
    ],
    'defaultEventConf' => [
        'name' => '英伦范头层牛皮剑桥包',
        'organizer' => '爱包包的女王工作室',
        'startTime' => time(),
        'endTime' => time() + 24 * 60 * 60 * 7,
        'lowestPrice' => 98,
        'adImages' => [
            'http://static-10006892.file.myqcloud.com/bargain/supplier/img/bannerImg-min.png',//默认轮播图
        ],
        'adLink' => '',
        'acquisitionTiming' => 0,
        'contact' => ['5899781b193e120b94e4e6cf', '58b4f291409b8c888e34e413'],
        'advancedSetting' => [
            'title' => '我给我家女王买个包',
            'description' => '女生如果一个月不买衣服包包鞋子，抑郁症发病率将明显高于同龄人',
            'image' => 'http://static-10006892.file.myqcloud.com/bargain/supplier/img/replyImg-min.png',
            'shareTitle' => '我在参加微砍价活动,大家快来帮我砍价吧!',
            'shareImage' => 'http://static-10006892.file.myqcloud.com/bargain/supplier/img/shareImg-min.png',
            'shareContent' => '小伙伴们，大家快来帮我砍价，我离奖品还差$元，助我一臂之力吧',
            'shareType' => 0
        ],
        'resources' => [
            'name' => '英伦范头层牛皮剑桥包',
            'number' => 99,
            'type' => 1,
            'price' => 288
        ]
    ],
    'defaultProbabilitySetting' => [
        'priceReduction' => 0.9,
        'priceReductionRange' => [6, 9],
        'priceIncrease' => 0.1,
        'priceIncreaseRange' => [1, 2]
    ],
    'defaultContent' => "
        <p>
            1、参与活动：点击“我也要抢”参与活动，先自己砍一砍练练手吧！&nbsp;
        </p>
        <p>
            2、当你砍到满意的价格，可用优惠价格购买
        </p>
        <p>
            3、本活动最终解释权归***所有。
        </p>
        <p>
            <br/>
        </p>
        ",
    'ocr_queue_cmq'=>'queue-idouzi-greennet-ocr-dev',//	图片和文字鉴黄消息队列名称
    'cmq' => [
        'region'=>'gz',
        'secretId'=>'AKIDeKItAxwWCDef6NIGltVXwk6HCTgztLQf',
        'endPoint'=>'cmq-queue-gz.api.tencentyun.com/v2/index.php',
        'secretKey'=>'IZLcVvpG6aSysStrapdgalf43RIgaJ5R',
        'endPointTopic'=>'cmq-topic-gz.api.tencentyun.com/v2/index.php'
    ],
    'isFreeButton' => true,//是否免费的开关
];
