<?php

namespace app\controllers;

use Idouzi\Commons\DomainUtil;
use Idouzi\Commons\SecurityUtil;
use Idouzi\Commons\StringUtil;
use app\controllers\filters\FreezeAccessFilter;
use app\controllers\filters\GetEventStaticDataFilter;
use app\controllers\filters\GetUserInfoFilter;
use app\controllers\filters\SetEventStaticDataFilter;
use app\controllers\filters\ThreeLevelDomainFilter;
use app\models\Bargain;
use app\models\BargainContribution;
use app\models\Event;
use app\models\RespMsg;
use app\services\bargain\BargainStrategyApi;
use Yii;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;

/**
 * 微信端请求控制器
 *
 * @package app\controllers
 */
class MobileController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        return [
            'threeLevelDomainFilter' => [
                'class' => ThreeLevelDomainFilter::className(),
                'actions' => ['index'],
            ],
            'getUserInfoFilter' => [
                'class' => GetUserInfoFilter::className(),
            ],
            'setEventStaticDataFilter' => [
                'class' => SetEventStaticDataFilter::className(),
                'actions' => ['index', 'get-base-info'],
            ],
            'getEventStaticDataFilter' => [
                'class' => GetEventStaticDataFilter::className(),
            ],
            'FreezeAccess' => [
                'class' => FreezeAccessFilter::className(),
                'actions' => ['index']
            ],
        ];
    }

    /**
     * 首页
     */
    public function actionIndex()
    {
        return $this->renderPartial('bargain');
    }

    /**
     * 获取页面基本信息
     * @param string $eventId 商家id
     * @param string $fromUrl 请求链接
     * @return RespMsg
     */
    public function actionGetBaseInfo($eventId, $fromUrl)
    {
        $respMsg = new RespMsg();
        // 判断该链接是否是分享链接
        if ($bargainId = Yii::$app->request->get('bargainId')) {
            //2.1 如果存在该砍价信息
            $queryArray = ['headImg', 'nickName', '_id', 'openId', 'price', 'bargainPrice', 'lowestPrice'];
            if ($bargain = Bargain::find()->select($queryArray)->where(['_id' => $bargainId])->one()) {
                Yii::$app->session->set('bargainId', $bargainId);
                $respMsg = BargainStrategyApi::infoBargain($eventId, $fromUrl, $bargain);
            } else {
                $respMsg->return_msg = '好像进错了分享链接哦，问问小伙伴是不是分享错链接吧';
                $respMsg->return_code = RespMsg::FAIL;
            }
        } else {
            $respMsg = BargainStrategyApi::infoBargain($eventId, $fromUrl);
        }
        return $respMsg;
    }


    /**
     * 砍价页面贡献列表
     * @return RespMsg
     */
    public function actionGetHelperList()
    {
        $respMsg = new RespMsg(['return_code' => RespMsg::FAIL]);
        $bargainStrategyApi = new BargainStrategyApi();
        if (!$bargainId = Yii::$app->request->get('bargainId')) {
            $eventId = Yii::$app->session->get('eventId');
            $bargain = Bargain::findOne([
                'eventId' => $eventId,
                'openId' => Yii::$app->session->get('oauth_info')['openid']
            ]);
            $bargainId = (string)$bargain['_id'];
        }
        $data = $bargainStrategyApi->getHelperList($bargainId);
        $respMsg->return_code = RespMsg::SUCCESS;
        $respMsg->return_msg = $data;
        return $respMsg;
    }

    /**
     * 获取砍价信息
     * @return RespMsg
     * @throws ForbiddenHttpException
     */
    public function actionGetBargainInfo()
    {
        $eventId = Yii::$app->session->get('eventId');
        $supplierId = Yii::$app->session->get('event_' . $eventId)['founder']['id'];
        //1. 判断是否带有bargainId
        if (Yii::$app->request->get('bargainId')) {
            //1.2 去查询是否存在当前砍价信息
            $bargain = Bargain::find()
                ->select([
                    '_id', 'openId', 'type', 'lowestPrice',
                    'resourceStatus', 'resourceExplain',
                    'bargainPrice', 'isLowestPrice', 'price'
                ])
                ->where(['_id' => Yii::$app->request->get('bargainId')
                ])->asArray()->one();
        } else {
            $bargain = Bargain::find()
                ->select(['_id', 'openId', 'type', 'lowestPrice',
                    'resourceStatus', 'resourceExplain',
                    'bargainPrice', 'isLowestPrice', 'price'
                ])
                ->where([
                    'eventId' => $eventId,
                    'openId' => Yii::$app->session->get('oauth_info')['openid']
                ])->asArray()->one();

        }
        //1.2.1 不存在则出错
        if (!$bargain) {
            throw new ForbiddenHttpException('访问出错啦，再刷新页面吧');
        }
        $mallId = isset(Yii::$app->session->get('event_' . $eventId)['resources']['mallId']) ?
            Yii::$app->session->get('event_' . $eventId)['resources']['mallId'] : $supplierId;
        return BargainStrategyApi::getBargainInfo($bargain, $mallId);
    }

    /**
     * 活动报名
     * @return RespMsg
     */
    public function actionJoin()
    {
        $respMsg = new RespMsg(['return_code' => RespMsg::FAIL]);
        //1. 判断是否参加过
        $isJoin = Bargain::find()->where([
            'eventId' => Yii::$app->session->get('eventId'),
            'openId' => Yii::$app->session->get('oauth_info')['openid']
        ])->count();
        if ($isJoin) {
            $respMsg->return_msg = '你已经参加过了，不能重复参加哦~';
            return $respMsg;
        }
        $stock = Event::find()->select(['resources.number'])->where(
            [
                '_id' => Yii::$app->session->get('eventId'),
            ]
        )->scalar();
        if (!$isJoin && !$stock['number']) {
            $respMsg->return_msg = '客官,商品已经被抢光了~';
            return $respMsg;
        }
        $event = Yii::$app->session->get('event_' . Yii::$app->session->get('eventId'));
        $bargain = new Bargain();
        //2. 参与时填写
        if (($event['acquisitionTiming'] == 0 && $event['resources']['type'] == 1) || $event['resources']['type'] == 0) {
            //2.1 判断是否有填写联系信息
            if (!Yii::$app->request->post('contact')) {
                $respMsg->return_msg = '请填写联系信息哦';
                return $respMsg;
            }
            //2.2 设置场景
            $bargain->setScenario('needContact');
            //2.3 判断是否传进来的内容都是活动需要的信息
            if (!$bargain->load(Yii::$app->request->post(), '') || !$bargain->validate()) {
                $respMsg->return_msg = current($bargain->getFirstErrors());
                return $respMsg;
            }
        }
        return $bargain->join();
    }

    /**
     * 砍价
     */
    public function actionBargain()
    {
        $respMsg = new RespMsg(['return_code' => RespMsg::FAIL]);
        $eventId = Yii::$app->session->get('eventId');
        if (Event::find()->select(['endTime'])->where(['_id' => $eventId])->scalar() <= time()) {
            $respMsg->return_msg = '活动已结束';
            return $respMsg;
        }
        if (Yii::$app->request->get('bargainId')) {
            //1. 去查询是否存在当前砍价信息
            $bargain = Bargain::findOne(Yii::$app->request->get('bargainId'));
        } else {
            //2. 获取砍价发起者的砍价信息
            $bargain = Bargain::findOne([
                'eventId' => $eventId,
                'openId' => Yii::$app->session->get('oauth_info')['openid']
            ]);
        }
        //3. 不存在该砍价
        if (!$bargain) {
            return new RespMsg(['return_code' => RespMsg::FAIL, 'return_msg' => '不存在这个砍价活动哦，请参加一个再砍吧~']);
        }
        // 已经是最低价，就不再砍价
        if ($bargain->isLowestPrice == 1) {
            $respMsg->return_msg = '已经最低价了，求放过T_T';
            return $respMsg;
        }
        // 砍过不能再砍
        $openId = Yii::$app->session->get('oauth_info')['openid'];
        $isJoin = BargainContribution::find()
            ->where(['bargainId' => $bargain->_id->__toString(), 'openId' => $openId])->count();
        if ($isJoin > 0) {
            $respMsg->return_msg = '亲，你已经砍过了哦，叫上其他小伙伴帮忙砍吧';
            return $respMsg;
        }
        return BargainStrategyApi::bargain($bargain);
    }

    /**
     * 去微商城购买
     * @return RespMsg
     */
    public function actionBuy()
    {
        $respMsg = new RespMsg();
        $eventId = Yii::$app->session->get('eventId');
        //如果不是微商城商品
        $mallId = isset(Yii::$app->session->get('event_' . $eventId)['resources']['mallId']) ?
            Yii::$app->session->get('event_' . $eventId)['resources']['mallId'] : null;
        if (Yii::$app->session->get('event_' . $eventId)['resources']['type'] != '0' || !$mallId) {
            throw new ForbiddenHttpException('访问出错');
        }
        $event = Event::find()->select(['resources.number'])->where(['_id' => $eventId])->one();
        //库存为 0
        if ($event->resources['number'] == 0) {
            $respMsg->return_msg = '商品太抢手啦，已经被抢光了';
            $respMsg->return_code = RespMsg::FAIL;
            return $respMsg;
        }
        $goodsId = Yii::$app->session->get('event_' . $eventId)['resources']['id'];
        $get = [
            'goodsId' => $goodsId,
            'eventId' => $eventId,
            'source' => 'bargain',
            'mallId' => Yii::$app->session->get('event_' . $eventId)['resources']['mallId']
        ];
        $respMsg->return_msg = DomainUtil::getTLD(Yii::$app->params['serviceUrl']['mallItemUrl']
            . '/mobile/bargain-goods-detail?' . http_build_query($get), $get['mallId']);
        return $respMsg;
    }

    /**
     * 用于获取兑换码
     * @return RespMsg
     */
    public function actionGetRedeemCode()
    {
        $eventId = Yii::$app->session->get('eventId');
        $openId = Yii::$app->session->get('oauth_info')['openid'];
        $respMsg = new RespMsg(['return_code' => RespMsg::FAIL]);
        //如果不是线下商品
        if (Yii::$app->session->get('event_' . $eventId)['resources']['type'] != '1') {
            throw new ForbiddenHttpException('访问出错');
        }
        //1.不是该砍价的属主
        if (!($bargain = Bargain::findOne(['eventId' => $eventId, 'openId' => $openId]))) {
            $respMsg->return_msg = '你不是该砍价的发起者哦~';
            return $respMsg;
        }
        //2. 已经有兑换码，直接返回
        if ($bargain->resourceExplain != '') {
            $respMsg->return_code = RespMsg::SUCCESS;
            $respMsg->return_msg = $bargain->resourceExplain;
            return $respMsg;
        }
        $event = Yii::$app->session->get('event_' . $eventId);
        //3. 兑奖时填写
        if ($event['acquisitionTiming'] == 1 && $event['resources']['type'] == 1) {
            //3.1 判断是否有联系信息
            if (!is_array(Yii::$app->request->post('contact')) || count(Yii::$app->request->post('contact')) == 0) {
                $respMsg->return_msg = '请填写领奖信息~';
                return $respMsg;
            }
            //3.2 设置场景
            $bargain->setScenario('needContact');
            //3.3 加载数据且校验 信息的规则
            if (!$bargain->load(Yii::$app->request->post(), '') || !$bargain->validate()) {
                $respMsg->return_msg = current($bargain->getFirstErrors());
                return $respMsg;
            }
        }
        // 判断是否最低价
        if ($bargain->isLowestPrice != 1) {
            $respMsg->return_msg = '还没砍到最低价，不能获取最低价哦';
            return $respMsg;
        }
        $number = Event::find()->select(['resources.number'])->where(['_id' => $eventId])->scalar();
        //判断库存是否为0
        if ($number['number'] == 0) {
            $respMsg->return_msg = '客官来慢一步了，商品已经兑换完了';
            $respMsg->return_code = RespMsg::FAIL;
            return $respMsg;
        }
        return $bargain->saveCashPrizeInfo();
    }
}