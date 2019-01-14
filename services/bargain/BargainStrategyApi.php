<?php

namespace app\services\bargain;

use app\commons\FunctionUtil;
use app\models\Bargain;
use app\models\BargainContribution;
use app\models\BargainProbability;
use app\models\Event;
use app\models\EventDescription;
use app\models\EventUvRecord;
use app\models\EventStatistics;
use app\models\RespMsg;
use app\services\field\FieldApi;
use app\services\weixin\WeiXinService;
use Idouzi\Commons\QCloud\TencentQueueUtil;
use yii\data\Pagination;
use Yii;
use app\exceptions\SystemException;

/**
 * 定义砍价策略对外接口
 */
class BargainStrategyApi implements BargainFacade
{

    /**
     * 返回公共数据
     * @param string $eventId 商家发起的活动id
     * @return RespMsg
     */
    public static function commonData(string $eventId)
    {
        $respMsg = new RespMsg();
        //2.获取活动说明
        $content = EventDescription::find()->select(['content'])->where(['eventId' => $eventId])->one();
        $data['eventInfo']['content'] = stripcslashes($content->content);

        //3.获取动态数据 : 参加人数和库存
        $realTimeData = Event::find()->select(
            [
                'virtualParticipants', 'resources.number', 'endTime', 'adLink', 'startTime', 'adImages', 'organizer',
                'advancedSetting.shareContent', 'advancedSetting.shareImage', 'advancedSetting.shareTitle',
                'advancedSetting.title', 'adsenseId'
            ]
        )->where(['_id' => $eventId, 'isDeleted' => 0])->asArray()->one();
        $event = Yii::$app->session['event_' . $eventId];//读取session数据

        //4.2组装活动数据
        $data['eventInfo']['isShowAd'] = isset($event['isShowAd']) ? $event['isShowAd'] : 1;
        $data['eventInfo']['adsenseId'] = isset($realTimeData['adsenseId']) && $realTimeData['adsenseId']
            ? $realTimeData['adsenseId'] : Yii::$app->params['office']['deployId'];
        $data['eventInfo']['flag'] = Yii::$app->params['flag'];
        $data['eventInfo']['participants'] = $realTimeData['virtualParticipants'];
        $data['eventInfo']['acquisitionTiming'] = $event['acquisitionTiming'];
        $data['eventInfo']['supplierId'] = $event['founder']['id'];
        $data['eventInfo']['name'] = $realTimeData['advancedSetting']['title'];
        $data['eventInfo']['expireIn'] = $realTimeData['endTime'] - time();
        $data['eventInfo']['organizer'] = $realTimeData['organizer'];
        $data['eventInfo']['contactInfo'] = FieldApi::getCustomField($event['contact'], 0);//创建活动的时候保证数据的准确
        $data['eventInfo']['qrcodeUrl'] = $event['founder']['qrcodeUrl'];
        $data['eventInfo']['status'] = $realTimeData['startTime'] > time()
            ? '未开始' : ($realTimeData['endTime'] <= time() ? '已结束' : '进行中');
        //4.3组装商品数据
        $data['goodsInfo']['adImages'] = $realTimeData['adImages'];
        $data['goodsInfo']['adLink'] = $realTimeData['adLink'];
        $data['goodsInfo']['lowestPrice'] = $event['lowestPrice'];
        $data['goodsInfo']['name'] = $event['resources']['name'];
        $data['goodsInfo']['number'] = $realTimeData['resources']['number'];
        $data['goodsInfo']['price'] = $event['resources']['price'];
        $data['goodsInfo']['type'] = $event['resources']['type'];
        $data['goodsInfo']['shopUrl'] = FunctionUtil::getTLD(
            Yii::$app->params['serviceUrl']['idouziWebUrl']
            . '/index.php?r=mobile/shop/index&wxid=' . $event['founder']['id'], $event['founder']['id']
        );
        //4.4组装分享信息
        $data['advancedSetting']['shareType'] = $event['advancedSetting']['shareType'];
        $data['wxShareInfo']['shareContent'] = $realTimeData['advancedSetting']['shareContent'];
        $data['wxShareInfo']['shareImage'] = $realTimeData['advancedSetting']['shareImage'];
        $data['wxShareInfo']['shareTitle'] = $realTimeData['advancedSetting']['shareTitle'];
        $respMsg->return_msg = $data;
        return $respMsg;
    }

    /**
     * 活动首页数据获取
     * @param string $eventId 商家发起的id
     * @param string $fromUrl 当前请求链接
     * @param Bargain|null $bargain 砍价模型，当分享砍价页面的时候需要带上
     * @return RespMsg
     */
    public static function infoBargain(string $eventId, string $fromUrl, $bargain = null)
    {
        //先组装基本数据
        $data = self::commonData($eventId);
        $str = '';
        //获取商家wxId
        $supplierId = Event::find()->select(['founder.id'])->where(['_id' => $eventId])->asArray()->one();
        $supplierId = $supplierId['founder']['id'];
        $openId = Yii::$app->session->get('oauth_info')['openid'];
        //消息队列保存用户uv、pv
        self::useQueueLoadUvAndPv($supplierId, $eventId, $openId);
        //3.$bargain 有值证明是分享砍价页面链接
        if ($bargain) {
            //判断该砍价是发起者还是帮砍者
            $data->return_msg['requestType'] = $bargain->openId == $openId ? 'sponsor' : 'helper';
            $data->return_msg['wxInfo']['headImg'] = $bargain->headImg;
            $data->return_msg['wxInfo']['nickName'] = $bargain->nickName;
        } else {
            //3.1不是分享链接
            $bargain = Bargain::find()->select(['headImg', 'nickName', '_id', 'price', 'bargainPrice', 'lowestPrice'])
                ->where(['eventId' => $eventId, 'openId' => Yii::$app->session->get('oauth_info')['openid']])
                ->one();
            //3.1.1 没有参与过活动，分享商品页面
            if (!$bargain) {
                $data->return_msg['requestType'] = 'index';
                $data->return_msg['wxInfo']['headImg'] = Yii::$app->session['oauth_info']['headimgurl'];
                $data->return_msg['wxInfo']['nickName'] = Yii::$app->session['oauth_info']['nickname'];
            } else {
                //3.1.2 参加过活动，分享的是自己的砍价页面
                $str = 'bargainId=' . $bargain->_id->__toString();
                $data->return_msg['requestType'] = 'sponsor';
                $data->return_msg['wxInfo']['headImg'] = $bargain->headImg;
                $data->return_msg['wxInfo']['nickName'] = $bargain->nickName;
            }
        }
        //显示具体砍掉价格
        if (!$data->return_msg['advancedSetting']['shareType'] && $bargain) {
            $nowPrice = sprintf("%.2f", $bargain->price - $bargain->bargainPrice - $bargain->lowestPrice);
            $data->return_msg['wxShareInfo']['shareContent'] = str_replace(
                '$',
                $nowPrice,
                $data->return_msg['wxShareInfo']['shareContent']
            );
        }
        unset($data->return_msg['advancedSetting']['shareType']);
        $wxId = Yii::$app->session->get('event_' . $eventId)['founder']['id'];
        //4.1 获取jsSdk配置失败
        if (($jsSdk = WeiXinService::returnJsSdkConf($wxId, $fromUrl, $str))->return_code == RespMsg::FAIL) {
            $data->return_code = RespMsg::FAIL;
            $data->return_msg = '获取jsSdk失败';
        } else {//4.2 获取jsSdk配置成功
            $data->return_msg['jsSdkConf'] = $jsSdk->return_msg;
        }
        //在session里保存传过来的链接
        Yii::$app->session->set('fromUrl', $fromUrl);
        return $data;
    }

    /**
     * 计算需要看多少刀才能到最低价  http://trac.idouzi.com/ticket/685
     * @param array $bargainProbabilityData =
     * [
     * 'priceReduction' , //降价概率
     * 'priceReductionRange' ,//降价范围
     * 'priceIncrease' ,//涨价概率
     * 'priceIncreaseRange',//涨价范围
     * 'price',//原价
     * 'lowestPrice',//最低价
     * ]
     * 计算方法：N=刀数 、Q=每刀价格
     *
     * 降价区间（A1-A2），概率（M%）
     *
     * 涨价区间（B1-B2），概率（100%-M%）
     *
     * N=（原价-低价）/每刀价格
     *
     * Q=每刀价格=降价X M% - 涨价X（100%-M%）; （结果向上舍入）
     *
     * Q1=A2X M%-B1X（100%-M%），Q2=A1X M%-B2X（100%-M%）
     *
     * N区间：（N1-N2）
     *
     * 最小值 N1=（原价-低价）/Q1=（原价-低价）/[A2X M%-B1X（100%-M%）]
     *
     * 最大值 N2=（原价-低价）/Q2=（原价-低价）/[ A1X M%-B2X（100%-M%）]
     *
     * 其中Q2小于等于0时，N2无穷大（表示怎么砍也不会砍到最低价）
     *
     * @param $price
     * @param $lowestPrice
     * @return array
     */
    public static function calculateBargainTimes(array $bargainProbabilityData, $price, $lowestPrice): array
    {
        //每一刀最大降价价格
        $mostPerPrice = $bargainProbabilityData['priceReductionRange'][1] * $bargainProbabilityData['priceReduction'] -
            $bargainProbabilityData['priceIncreaseRange'][0] * $bargainProbabilityData['priceIncrease'];
        //每一刀最小降价价格
        $leastPerPrice = $bargainProbabilityData['priceReductionRange'][0] * $bargainProbabilityData['priceReduction'] -
            $bargainProbabilityData['priceIncreaseRange'][1] * $bargainProbabilityData['priceIncrease'];
        //除数不能为0，当为0时即存在无法看到最低价 ，此时赋值负数即可
        if ($mostPerPrice == 0) {
            $mostPerPrice = -1;
        }
        if ($leastPerPrice == 0) {
            $leastPerPrice = -1;
        }
        //原价和最低价差价
        $diff = $price - $lowestPrice;
        $leastTimes = bcdiv($diff, $mostPerPrice);
        $mostTimes = bcdiv($diff, $leastPerPrice);
        $leastTimes = self::returnInt($leastTimes);
        $mostTimes = self::returnInt($mostTimes);
        return ['leastTimes' => $leastTimes, 'mostTimes' => $mostTimes];
    }

    /**
     * 返回计算刀数后，使数据以int返回
     * @param float $num
     * @return int
     */
    public static function returnInt(float $num): int
    {
        return $num >= 0 ? (is_int($num) ? $num : ((int)$num + 1)) : ((int)$num - 1);
    }

    /**
     * 砍到的价格
     * @param array $bargainProbability
     * @return array
     */
    public static function changePrice(array $bargainProbability)
    {
        //降价
        if (($diffPrice = self::reducePrice($bargainProbability['priceReduction'])) == -1) {
            return ['diffPrice' => $diffPrice, 'bargainPrice' =>
                (double)sprintf("%.2f", mt_rand(
                        $bargainProbability['priceReductionRange'][0] * 100,
                        $bargainProbability['priceReductionRange'][1] * 100
                    ) / 100)];
        } else {//涨价
            return ['diffPrice' => $diffPrice, 'bargainPrice' =>
                (double)sprintf("%.2f", mt_rand(
                        $bargainProbability['priceIncreaseRange'][0] * 100,
                        $bargainProbability['priceIncreaseRange'][1] * 100
                    ) / 100)];
        }
    }

    /**
     * 返回这次是否降价
     * 一个平均的判断策略，以降价的概率为低位，大于砍价的概率即涨价
     * 如 80%的降价概率，若随机数大于80，则为涨价
     * @param $priceReduction
     * @return int -1为降价，1为涨价, 为价格正负符号
     */
    public static function reducePrice($priceReduction)
    {
        return mt_rand(1, 100) <= 100 * $priceReduction ? -1 : 1;
    }

    /**
     * 真正去获取砍价信息的地方
     * @param array $bargain
     * @param string $mallId
     * @return RespMsg
     */
    public static function getBargainInfo(array $bargain, string $mallId)
    {
        $respMsg = new RespMsg();
        //1. 判断用户是否砍过价
        $isBargain = BargainContribution::find()->where(['bargainId' => $bargain['_id']->__toString(),
            'openId' => Yii::$app->session->get('oauth_info')['openid']])->count();
        $data['eventInfo']['isBargain'] = $isBargain ? true : false;
        //2. 是否是砍价的属主
        if ($bargain['openId'] == Yii::$app->session['oauth_info']['openid']) {
            //是否已购买或已兑奖
            $data['goodsInfo']['exchangeStatus'] = $bargain['type'] == 0 ?
                ($bargain['resourceStatus'] != '正在砍' && !empty($bargain['resourceExplain']) ? true : false) :
                $bargain['resourceStatus'];
            if ($bargain['resourceExplain']) {
                $url = Yii::$app->params['serviceUrl']['mallOrder'] . '/mobile/order-list?mallId=' .
                    $mallId . '&orderId=' . $bargain['resourceExplain'];
                $flag = FunctionUtil::getTLD($url, $mallId);
            } else {
                $flag = $bargain['resourceExplain'];
            }
            $data['goodsInfo']['relateInfo'] = $bargain['type'] == 0 ? $flag : $bargain['resourceExplain'];
        } else {
            $data['goodsInfo']['exchangeStatus'] = '';
            $data['goodsInfo']['relateInfo'] = '';
        }
        //3. 组装砍价的信息
        $data['eventInfo']['disparityPrice'] = $bargain['bargainPrice'];//已砍价格
        $data['eventInfo']['isLowestPrice'] = $bargain['isLowestPrice'];//是否最低价格
        $data['eventInfo']['progress'] = round($bargain['bargainPrice'] / ($bargain['price'] - $bargain['lowestPrice']), 2);//砍价进度
        $respMsg->return_msg = $data;
        return $respMsg;
    }

    /**
     * 执行砍价
     * @param Bargain $bargain 商家发起的活动砍价Id
     * @return RespMsg
     */
    public static function bargain($bargain)
    {
        $respMsg = new RespMsg();

        //2. 获取砍价概率信息
        $bargainProbability = BargainProbability::find()->select(['_id' => null, 'eventId' => null])
            ->where(['eventId' => $bargain->eventId])->asArray()->one();
        //2.1 获取砍了多少金额，返回的是数组
        $afterBargain = BargainStrategyApi::changePrice($bargainProbability);

        //3. 计算 砍前商品价格 = 原价-已砍总价格
        $beforePrice = $bargain->price - $bargain->bargainPrice;
        //3.1 把这次砍了的金额带上符号,第一次砍价必须降价，不能涨价
        $bargainPrice = $bargain->bargainPrice ? $afterBargain['diffPrice'] * $afterBargain['bargainPrice'] : (-1 * $afterBargain['bargainPrice']);
        $bargainPrice = substr(sprintf("%.3f", $bargainPrice), 0, -1);
        //3.2 计算 砍后商品价格=砍前价格+当次砍掉价格
        $afterPrice = $beforePrice + $bargainPrice;
        //3.3 如果砍到最低价
        if ($afterPrice <= $bargain->lowestPrice) {
            $bargainPrice = $bargain->lowestPrice - $beforePrice;//已经小于0
            $bargainUpdate = [
                'isLowestPrice' => 1, 'endTime' => time(), 'bargainPrice' => $bargain->price - $bargain->lowestPrice,
                'version' => ($bargain->version + 1)
            ];
            if ($bargain->type == 0) {
                $bargainUpdate['resourceStatus'] = '已砍完';
            }
            //实例化砍价贡献列表
            $bargainContribution = new BargainContribution(['diffPrice' => $afterBargain['diffPrice'],
                'beforePrice' => $beforePrice, 'afterPrice' => $bargain->lowestPrice, 'bargainId' => $bargain->_id->__toString()]);
        } else {
            //砍了多少钱，所以负数反而是加
            $bargainUpdate = [
                '$inc' => ['bargainPrice' => -1 * $bargainPrice, 'version' => 1]
            ];
            //实例化砍价贡献列表
            $bargainContribution = new BargainContribution(['diffPrice' => $afterBargain['diffPrice'],
                'beforePrice' => $beforePrice, 'afterPrice' => $afterPrice, 'bargainId' => $bargain->_id->__toString()]);
        }
        $result = self::doSaveBargain($bargain, $bargainContribution, $bargainUpdate, $bargainPrice);
        if ($result->return_code == 'FAIL') {
            return $result;
        }
        $bargain = $result->return_msg;
        $respMsg->return_msg = ['bargainTime' => date('Y-m-d H:i', $bargainContribution->bargainTime),
            'totalBargain' => $bargain->bargainPrice, 'bargainPrice' => $bargainPrice,
            'isLowestPrice' => $bargain->isLowestPrice,
            'headImg' => Yii::$app->session->get('oauth_info')['headimgurl'],
            'nickName' => Yii::$app->session->get('oauth_info')['nickname'],
            'progress' => round($bargain->bargainPrice / ($bargain->price - $bargain->lowestPrice), 2)];
        return $respMsg;
    }

    /**
     * 去保存砍价信息，并插入一条贡献信息,当处于高并发时，三次重试
     * @param Bargain $bargain bargain模型
     * @param BargainContribution $bargainContribution
     * @param array $bargainUpdate
     * @param  double $bargainPrice 带符号的该次砍掉的价格
     * @return RespMsg
     */
    public static function doSaveBargain(Bargain $bargain, BargainContribution $bargainContribution, $bargainUpdate, $bargainPrice)
    {
        $retryTimes = 0;//重试计数
        $respMsg = new RespMsg();
        while ($retryTimes < 3) {//当处于多人砍价时，最多重试三次
            $int = Bargain::updateAll($bargainUpdate,
                [
                    '_id' => $bargain->_id->__toString(),
                    'isLowestPrice' => 0,
                    'version' => $bargain->version,
                    'bargainPrice' => ['$lt' => $bargain->price - $bargain->lowestPrice]
                ]);
            //砍价表保存不成功
            if ($int != 1) {
                //重试
                $bargain = Bargain::findOne(['_id' => $bargain->_id->__toString()]);
                $retryTimes++;
                continue;
            }
            //砍价表保存成功，插入贡献列表
            BargainContribution::_beforeBargainSave($bargainContribution, Yii::$app->session->get('oauth_info'));
            if (!$bargainContribution->insert()) {//保存贡献失败的话回滚并返回失败
                //原本如果是最低价
                if (isset($bargainUpdate['isLowestPrice'])) {
                    $bargainUpdate['isLowestPrice'] = 0;
                    $bargainUpdate['endTime'] = 0;
                }
                //取原来的价格
                $bargainUpdate['bargainPrice'] = $bargain->bargainPrice;
                Bargain::updateAll($bargainUpdate,
                    [
                        '_id' => $bargain->_id->__toString(),
                        'bargainPrice' => ['$lte' => $bargain->price - $bargain->lowestPrice - $bargainPrice]
                    ]);
                Yii::warning('bargainContribution model  have a insert error:'
                    . json_encode($bargainContribution->getErrors()) . ' and attr = '
                    . json_encode($bargainContribution), __METHOD__);
                $respMsg->return_code = RespMsg::FAIL;
                $respMsg->return_msg = '当前砍价的人太多了，再试试吧';
                return $respMsg;
            }
            //把最后的更新信息返回
            $respMsg->return_msg = Bargain::find()->select(['bargainPrice', 'price', 'lowestPrice', 'isLowestPrice'])
                ->where(['_id' => $bargain->_id->__toString()])->one();
            return $respMsg;
            break;
        }
        $respMsg->return_code = RespMsg::FAIL;
        $respMsg->return_msg = '当前砍价的人太多了，再试试吧';
        return $respMsg;
    }

    /**
     * @param $bargainId
     * @return RespMsg
     * 获取贡献列表
     */
    public function getHelperList(string $bargainId)
    {
        $listInfo = BargainContribution::find()->select(['nickName', 'headImg', 'diffPrice',
            'bargainTime', 'beforePrice', 'afterPrice'])
            ->where(['bargainId' => $bargainId]);
        $pageSize = 6;
        $offset = (Yii::$app->request->get('page') - 1) * $pageSize;
        $page = new Pagination(['totalCount' => $listInfo->count(), 'pageSize' => $pageSize]);
        $data = $listInfo->orderBy('bargainTime desc')->offset($offset)->limit($page->limit)->asArray()->all();
        foreach ($data as $key => $val) {
            $data[$key]['disparity'] = round(sprintf("%.3f",
                ($data[$key]['afterPrice'] - $data[$key]['beforePrice'])), 6);
            $data[$key]['bargainTime'] = date("Y-m-d H:i:s", $data[$key]['bargainTime']);
        }
        return $data;
    }

    /**
     * 根据event_uv_record表判断uv是否含有，不含有则做写入操作
     * @param $supplierId
     * @param $eventId
     * @param $openId
     * @return bool|null
     */
    public static function judgeUv($supplierId, $eventId, $openId)
    {
        $date = date('Ymd', time());
        $eventUvRecord = new EventUvRecord();
        $eventUvRecord->date = $date;
        $count = $eventUvRecord->find()->from($eventUvRecord->getTableName())->where(['openId' => $openId, 'eventId' => $eventId, 'date' => $date])->count();
        if ($count > 0) {
            return null;
        }

        if ($eventUvRecord->insertOpenId($supplierId, $eventId, $openId)) {
            return true;
        }
        return null;
    }

    /**
     * 重组api中GetBarginInfo方法返回数据
     * @param $eventQueryResult
     * @return mixed
     */
    public static function reSetData($eventQueryResult)
    {
        if (!is_array($eventQueryResult) || empty($eventQueryResult)) {
            Yii::warning('组装数据失败传入参数不是有效的数组!');
            return false;
        }
        foreach ($eventQueryResult as &$value) {
            foreach ($value['source'] as $v) {
                if (isset($v['uv']) && isset($v['pv'])) {
                    $value['count'] += $v['pv'];
                    $value['count_uv'] += $v['uv'];
                }
            }
        }
        return $eventQueryResult;
    }


    private static function useQueueLoadUvAndPv($supplierId, $eventId, $openId)
    {
        try{
            $data = [
                'supplierId' => $supplierId,
                'eventId' => $eventId,
                'openId' => $openId,
            ];
            //将用户信息保存到腾讯消息队列和订阅
            TencentQueueUtil::publishMessage(Yii::$app->params['topic-bargain-uvRecord'], json_encode($data));
        }catch (\Exception $e){
            Yii::warning('eventUvRecord记录到消息队列存储报错'.$e->getMessage());
        }

    }


    /**
     * 根据起始时间查出需要数据然后在做分表插入处理
     *
     * @param $time
     * @return bool
     * @throws SystemException
     */
    public static function translateUvData($time)
    {
        $eventUv = new EventUvRecord();
        for ($i = 0; $i < 10000; $i++) {
            $date = date('Ymd', strtotime("-$i day",strtotime($time)));
            $eventUv->date = (string)$date;
            $eventUvData = $eventUv->find()->select(["eventId", "openId", "supplierId", "date"])
                ->where(["date" => "$date"])->asArray()->all();
            if (!$eventUvData) {
                throw new SystemException('eventUvRecord表' . '查询失败第' . $date . '天出现错误');
            }
            //批量插入数据
            $eventUv->getBatchInsert($eventUvData);
        }
        return true;
    }
}