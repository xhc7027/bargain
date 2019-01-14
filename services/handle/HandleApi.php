<?php

namespace app\services\handle;

use app\commons\HttpUtil;
use app\commons\SecurityUtil;
use app\commons\StringUtil;
use app\exceptions\SystemException;
use app\models\Bargain;
use app\models\Event;
use app\models\RespMsg;
use Yii;
use yii\data\Pagination;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/16 0016
 * Time: 下午 2:02
 */
class HandleApi implements HandleFacade
{

    /**
     * @var array 存放商品订单信息
     */
    public $shopOrderInfo = [];

    /**
     * 进行兑奖操作
     * @param string $bargainId --参与者所属的id
     * @return mixed
     */
    public static function doCashPrize(string $bargainId)
    {
        $bargain = Bargain::find()->select(['resourceStatus'])->where(['_id' => $bargainId])->one();
        $bargain->resourceStatus = '已兑奖';
        return $bargain->save();
    }

    /**
     * 用于获取商品列表
     * @param array $params 请求get参数
     * @return RespMsg
     */
    public function getShopInfo(array $params)
    {
        $url = Yii::$app->params['serviceUrl']['idouziUrl'] . '/supplier/api/getShopInfo';
        $return = HttpUtil::get($url, http_build_query($params));
        if ($return->return_code == 'SUCCESS' && $return->return_msg->status == 1) {//1代表有商品
            $return->return_msg = HandleApi::returnShopInfo($return->return_msg);
            return $return;
        } elseif ($return->return_code == 'SUCCESS' && $return->return_msg->status == -2) {//-2代表不存在查询商品
            return new RespMsg(['return_code' => RespMsg::FAIL, 'return_msg' => $return->return_msg->msg]);
        }
        Yii::warning('get shop info failed:' . $return->return_msg->msg, __METHOD__);
        throw new SystemException('获取异常，请重新尝试');
    }

    /**
     * 用于返回给微商城判断砍价者的商品信息的实现
     * @param string $eventId
     * @param string $openId
     * @return RespMsg
     */
    public static function getMallNeedInfo(string $eventId, string $openId)
    {
        $event = Event::find()->select(['resources.id', 'resources.number'])->where(['_id' => $eventId, 'isDeleted' => 0])->asArray()->one();
        if (!$event) {//找不到活动
            return new RespMsg(['return_code' => RespMsg::FAIL, 'return_msg' => '砍价活动信息不对']);
        }
        $bargain = Bargain::find()->select(['_id', 'price', 'bargainPrice', 'resourceExplain', 'lowestPrice'])
            ->where(['eventId' => $eventId, 'openId' => $openId])->asArray()->one();
        if (!$bargain) {//找不到对应参与人信息
            return new RespMsg(['return_code' => RespMsg::FAIL, 'return_msg' => '购买商品与参与人信息不符']);
        }
        $data = [
            'goodsId' => $event['resources']['id'],
            'number' => $event['resources']['number'],
            'bargainUserId' => (string)$bargain['_id'],
            'price' => $bargain['price'],
            'nowPrice' => round($bargain['price'] - $bargain['bargainPrice'], 2),
            'resourceExplain' => $bargain['resourceExplain'],
            'lowestPrice' => $bargain['lowestPrice'],
            'state' => Yii::$app->request->get('state')
        ];//组装数据
        return new RespMsg(['return_msg' => $data]);
    }

    /**
     * 用于给微商城更改订单状态
     * @param array $data
     * @return RespMsg
     */
    public function updateShopInfo(array $data)
    {
        $respMsg = new RespMsg(['return_code' => RespMsg::FAIL]);
        $this->shopOrderInfo = $data;
        try {
            //创建订单
            if ($data['do'] == 'create') {
                return $this->create();
            } elseif ($data['do'] == 'update') {//更新订单
                return $this->update();
            }
        } catch (\Exception $e) {
            Yii::warning('更新订单状态失败：' . $e->getMessage(), __METHOD__);
            $respMsg->return_msg = $e->getMessage();
        }
        return $respMsg;
    }

    /**
     * 进行创建订单操作
     * @return RespMsg
     */
    private function create()
    {
        $respMsg = new RespMsg();
        $bargain = Bargain::findOne([
            '_id' => $this->shopOrderInfo['bargainId'],
            'openId' => $this->shopOrderInfo['openId'],
        ]);
        //错误的砍价id
        if (!$bargain) {
            $respMsg->return_msg = '购买商品与参与人信息不符';
            return $respMsg;
        }
        //更改库存失败
        if (!self::changeResourcesNumber($bargain->eventId, $this->shopOrderInfo['status'])) {
            return $respMsg;
        }
        $bargain->resourceExplain = $this->shopOrderInfo['orderId'];
        $bargain->resourceStatus = Yii::$app->params['shopStatus'][$this->shopOrderInfo['status']];
        $bargain->updateTime = time();
        $contact = $bargain->contact;
        $contact['address'] = $this->shopOrderInfo['address'];
        $bargain->contact = $contact;
        if (!$bargain->update()) {
            //失败则回滚
            self::rollbackResourcesNumber($bargain->eventId, $this->shopOrderInfo['status']);
            Yii::warning('创建订单失败,订单号:' . $this->shopOrderInfo['orderId'] . '砍价id：' . $this->shopOrderInfo['bargainId']
                . 'openId:' . $this->shopOrderInfo['openId'] . 'status:' . $this->shopOrderInfo['status'], __METHOD__);
            $respMsg->return_msg = '创建订单失败';
        }
        return $respMsg;
    }

    /**
     * 执行更新订单操作
     * @return RespMsg
     */
    private function update()
    {
        $respMsg = new RespMsg();
        $bargain = Bargain::findOne(['resourceExplain' => $this->shopOrderInfo['orderId'], 'type' => 0]);
        if (!$bargain) {
            $respMsg->return_msg = '不存在此订单';
            return $respMsg;
        }
        //订单状态没有改变,返回正确
        if ($bargain->resourceStatus == Yii::$app->params['shopStatus'][$this->shopOrderInfo['status']]) {
            $respMsg->return_code = RespMsg::SUCCESS;
            return $respMsg;
        }
        //更改库存失败
        if (!self::changeResourcesNumber($bargain->eventId, $this->shopOrderInfo['status'])) {
            return $respMsg;
        }
        $bargain->resourceStatus = Yii::$app->params['shopStatus'][$this->shopOrderInfo['status']];
        $bargain->updateTime = time();
        if (!$bargain->update()) {
            //失败则回滚
            self::rollbackResourcesNumber($bargain->eventId, $this->shopOrderInfo['status']);
            Yii::warning('更新订单失败,订单号:' . $this->shopOrderInfo['orderId'] . 'status:' . $this->shopOrderInfo['status'], __METHOD__);
            $respMsg->return_msg = '更新订单失败';
        }
        return $respMsg;
    }

    /**
     * 对库存进行数量更改操作
     * @param $eventId
     * @param $resourceStatus
     * @return bool
     */
    public static function changeResourcesNumber($eventId, $resourceStatus)
    {
        //不在更改库存状态的名单内则不执行
        if (!array_key_exists($resourceStatus, Yii::$app->params['needToUpdateStatus'])) {
            return true;
        }

        //属于减库存的状态
        if (Yii::$app->params['needToUpdateStatus'][$resourceStatus] == 'minus') {
            $retRow = Event::updateAll(['$inc' => ['resources.number' => -1]],
                ['_id' => $eventId, 'resources.number' => ['$gt' => 0]]);
            if ($retRow == 1) {
                return true;
            }
        } elseif (Yii::$app->params['needToUpdateStatus'][$resourceStatus] == 'add') {//属于加库存的状态
            $retRow = Event::updateAll(['$inc' => ['resources.number' => 1]], ['_id' => $eventId]);
            if ($retRow == 1) {
                return true;
            }
        }
        return false;
    }

    /**
     * 对库存进行数量回滚操作
     * @param $eventId
     * @param $resourceStatus
     * @return bool
     */
    public static function rollbackResourcesNumber($eventId, $resourceStatus)
    {
        //只对在属于对库存有更改的状态进行操作
        if (array_key_exists($resourceStatus, Yii::$app->params['needToUpdateStatus'])) {

            //属于减库存的状态
            if (Yii::$app->params['needToUpdateStatus'][$resourceStatus] == 'minus') {
                $retRow = Event::updateAll(['$inc' => ['resources.number' => 1]], ['_id' => $eventId]);
                if ($retRow == 1) {
                    return true;
                }
            } elseif (Yii::$app->params['needToUpdateStatus'][$resourceStatus] == 'add') {//属于加库存的状态
                $retRow = Event::updateAll(['$inc' => ['resources.number' => -1]],
                    ['_id' => $eventId, 'resources.number' => ['$gt' => 0]]);
                if ($retRow == 1) {
                    return true;
                }
            }
            return false;
        }
        return true;
    }

    /**
     * 退款后砍价库存回滚
     *
     * @param $orderId
     * @return mixed
     * @throws SystemException
     */
    public static function rollbackResourcesNumberByRefund($orderId)
    {
        $bargainInfo = Bargain::findOne(['resourceExplain' => $orderId]);;
        if ($bargainInfo['eventId']) {
            //只对在属于对库存有更改的状态进行操作
            $retRow = Event::updateAll(['$inc' => ['resources.number' => 1]], ['_id' => $bargainInfo['eventId']]);
            if ($retRow == 1) {
                $bargainInfo->resourceStatus = Yii::$app->params['shopStatus']['8'];
                $bargainInfo->update();
                return true;
            }
            throw new SystemException('回滚库存失败：orderId' . $orderId);
        }
    }

    /**
     * 生成兑换码
     * @param $bargain
     */
    public function createRedeemCode($bargain)
    {
        $flag = true;
        while ($flag) {//生成唯一的兑换码
            $bargain->resourceExplain = StringUtil::genRandomStr(8);
            $flag = Bargain::findOne(['eventId' => $bargain->eventId, 'resourceExplain' => $bargain->resourceExplain]);
        }
        // 更新兑换状态
        $bargain->resourceStatus = '未兑奖';
    }

    /**
     * 通过向爱豆子请求数据后，将返回的商城信息包装成我们需要的样子
     * @param $data
     * @return array
     * {
     * "return_code": "SUCCESS",
     * "return_msg": {
     * "goods": [
     * {
     * "create_time": "1488795596",
     * "goods_price": "11.00",
     * "img": "http://idouziimg-10006892.image.myqcloud.com/20170306181327_29830?imageMogr2/crop/768x768",
     * "goods_name": "双方商定",
     * "sales": "1",
     * "stock": "20"
     * }
     * ],
     * "cate": [
     * {
     * "id": "994",
     * "name": "推广工具"
     * },
     * ],
     * "totalPage": 1
     * }
     * }
     */
    public static function returnShopInfo($data)
    {
        $returnData = [];
        if (isset($data->goods) && count($data->goods) > 0) {
            foreach ($data->goods as $k => $val) {
                $returnData['goods'][$k]['create_time'] = $val->create_time;
                $returnData['goods'][$k]['goods_price'] = $val->goods_price;
                $returnData['goods'][$k]['img'] = $val->img;
                $returnData['goods'][$k]['goods_name'] = $val->goods_name;
                $returnData['goods'][$k]['sales'] = $val->sales;
                $returnData['goods'][$k]['stock'] = $val->stock;
                $returnData['goods'][$k]['id'] = $val->goods_id;
            }
        }
        if (isset($data->goods_cates) && count($data->goods_cates) > 0) {
            foreach ($data->goods_cates as $k => $val) {
                $returnData['cate'][$k]['id'] = $val->id;
                $returnData['cate'][$k]['name'] = $val->name;
            }
        }
        if (isset($data->page->count) && !empty($data->page->count)) {
            $pagination = new Pagination(
                ['pageSize' => 8, 'totalCount' => $data->page->count]
            );
            $returnData['totalPage'] = $pagination->getPageCount();
        }
        return $returnData;
    }

    /**
     * 向爱豆子发送更新订单状态的请求
     * @param $bargains
     * @return mixed
     */
    public static function toSendForOrders($bargains)
    {
        //2. 组装请求数据，以订单号为key
        $sendPostData = [];
        foreach ($bargains as $bargain) {
            $sendPostData[$bargain['resourceExplain']] = '';//请求的数组
        }
        //2.1 生成签名
        $get = ['timestamp' => time(), 'state' => StringUtil::genRandomStr(16)];
        $get['sign'] = (new SecurityUtil($get, Yii::$app->params['signKey']['mallOrderSignKey']))->generateSign();

        return json_decode(
            HttpUtil::post(Yii::$app->params['serviceUrl']['mallOrder'] . '/api/bargain-get-orders-status?'
                . http_build_query($get), $sendPostData), true
        );
    }

    /**
     * 获取商家优惠券列表
     *
     * @param int $supplierId 商家id
     * @return array
     */
    public static function getSupplierCoupon($supplierId)
    {
        return self::getInfoFromShop('query-supplier-coupon', $supplierId);
    }

    /**
     * 获取商家未查看优惠券数据
     *
     * @param int $supplierId 商家id
     * @return array
     */
    public static function queryNoReadCount($supplierId)
    {
        return self::getInfoFromShop('query-no-read-num', $supplierId);
    }

    /**
     * 从商城获取所需信息
     *
     * @param int $supplierId 商家id
     * @return array
     */
    public static function getInfoFromShop($action, $supplierId)
    {
        try {
            $get = array(
                'timestamp' => time(),
                'state' => StringUtil::genRandomStr(),
            );//拼装get参数
            $post = ['supplierId' => $supplierId, 'couponState' => 0];
            $url = Yii::$app->params['serviceUrl']['MALL_URL'] . '/api/' . $action . '?';
            $get['sign'] = (new SecurityUtil($get, Yii::$app->params['signKey']['mallSignKey']))->generateSign();
            $url .= http_build_query($get);
            $resp = json_decode(HttpUtil::simplePost($url, $post), true);
            //1.1 获取成功
            if (isset($resp['return_code']) && $resp['return_code'] === 'SUCCESS') {
                return $resp['return_msg'];
            }
            Yii::warning('从商城获取数据失败，error=' . json_encode($resp), __METHOD__);
        } catch (\Exception $e) {
            Yii::warning('从商城获取数据失败，error=' . json_encode($e->getMessage()), __METHOD__);
        }

        return null;
    }
}