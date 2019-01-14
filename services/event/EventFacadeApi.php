<?php

namespace app\services\event;

use app\commons\DomainUtil;
use app\commons\HttpUtil;
use app\commons\SecurityUtil;
use app\commons\SendOrcMsg;
use app\commons\StringUtil;
use app\exceptions\SystemException;
use app\models\BargainProbability;
use app\models\EventDescription;
use app\models\RespMsg;
use app\services\bargain\BargainStrategyApi;
use app\models\Event;
use app\models\Bargain;
use app\models\BargainContribution;
use app\services\SessionService;
use Idouzi\Commons\NodeApiUtil;
use yii\data\Pagination;
use app\services\field\FieldApi;
use yii;
use yii\base\Exception;

/**
 * 活动管理模块数据操作接口实现
 */
class EventFacadeApi implements EventFacade
{

    /**
     * @var string 活动id
     */
    public $eventId;

    /**
     * 活动页面数据获取
     *
     * @param string|null $eventId 砍价活动id
     * @param string $from 判断是来自创建还是编辑还是复制：create代表创建，edit代表编辑，copy代表复制
     * @return mixed
     */
    public function getBargainData($eventId, string $from)
    {
        $function = $from . 'Bargain';
        $this->eventId = empty($eventId) ? '' : $eventId;

        //返回数据
        return $this->$function();

    }

    /**
     * 返回新建活动时的默认数据
     *
     * @throws Exception
     * @return RespMsg
     */
    public function createBargain()
    {
        //读取默认数据配置
        $eventData = Yii::$app->params['defaultEventConf'];
        //组装数据
        $eventData['mallStatus'] = self::getNewMallStatus();

        return $this->assembleEventData($eventData);
    }

    /**
     * 返回编辑活动时的数据
     *
     * @return RespMsg
     * @throws Exception
     */
    public function editBargain()
    {
        $eventData = Event::find()->select(['_id' => null])->where(['_id' => $this->eventId, 'isDeleted' => 0])
            ->asArray()->one();
        if (!$eventData) {
            Yii::warning('活动数据不存在', __METHOD__);
            throw new SystemException('不存在该活动');
        }
        $eventData = $this->assembleEventData($eventData);
        $defaultData = Yii::$app->params['defaultEventConf'];//高级设置那里添加默认设置
        unset($eventData->return_msg['createdTime'],
            $eventData->return_msg['updatedTime'], $eventData->return_msg['founder']);
        foreach ($defaultData['advancedSetting'] as $key => $val) {
            $eventData->return_msg['advancedSetting']['default' . ucfirst($key)] = $val;
        }
        //编辑时判断是否是进行中的活动，1为已开始，0为未开始
        $eventData->return_msg['eventStart'] = 0;
        if (time() >= $eventData->return_msg['startTime']) {
            $eventData->return_msg['eventStart'] = 1;
        }
        $eventData->return_msg['mallStatus'] = self::getNewMallStatus();
        return $eventData;
    }

    /**
     * 返回复制活动时的数据
     *
     * @return RespMsg
     * @throws Exception
     */
    public function copyBargain()
    {
        $respMsg = new RespMsg();
        $eventData = Event::find()->select(['_id' => null])->where(['_id' => $this->eventId, 'isDeleted' => 0])->asArray()->one();
        if (!$eventData) {
            Yii::warning('活动数据不存在', __METHOD__);
            throw new SystemException('不存在该活动');
        }
        $eventData = $this->assembleEventData($eventData);

        $defaultData = Yii::$app->params['defaultEventConf'];//高级设置那里添加默认设置
        foreach ($defaultData['advancedSetting'] as $key => $val) {
            $eventData->return_msg['advancedSetting']['default' . ucfirst($key)] = $val;
        }
        //将某些需要用户自己填写的数据置空
        $eventData->return_msg['adLink'] = '';
        $eventData->return_msg['advancedSetting']['keyword'] = '';
        $eventData->return_msg['mallStatus'] = self::getNewMallStatus();
        return $eventData;
    }

    /**
     * 用于组装返回的最后数据
     *
     * @param $eventData
     * @return RespMsg
     */
    public function assembleEventData($eventData)
    {
        //有活动id，非创建活动
        if ($this->eventId) {
            $eventData['bargainProbability'] = BargainProbability::getProbabilityById($this->eventId);
            //活动说明和联系信息
            $eventData['content'] = stripcslashes(EventDescription::getDescriptionByEventId($this->eventId));
        } else {
            $eventData['bargainProbability'] = Yii::$app->params['defaultProbabilitySetting'];
            //活动说明和联系信息
            $eventData['content'] = Yii::$app->params['defaultContent'];
        }
        //获取砍价刀数
        $eventData['priceTimes'] = BargainStrategyApi::calculateBargainTimes(
            $eventData['bargainProbability'],
            $eventData['resources']['price'], $eventData['lowestPrice']
        );
        $eventData['shopUrl'] = Yii::$app->params['serviceUrl']['idouziUrl'] . '/supplier/shop/addGoods';
        $eventData['contact'] = FieldApi::getCustomField($eventData['contact']);
        if (self::collatingEndTime() && !Yii::$app->params['isFree']) {
            $endTime = EventFacadeApi::getSupplierFreeUseTime(Yii::$app->session->get('userAuthInfo')['supplierId']);
            $eventData['endTime'] = $endTime ? strtotime($endTime . '23:59:59') : $eventData['endTime'];
        }

        return new RespMsg(['return_msg' => $eventData]);
    }

    /**
     * 统一的保存数据时的校验函数
     *
     * @param Event $event
     * @param BargainProbability $bargainProbability
     * @param EventDescription $eventDescription
     * @return RespMsg|bool
     */
    public static function validate(Event $event, BargainProbability $bargainProbability, EventDescription $eventDescription)
    {
        $respMsg = new RespMsg(['return_code' => RespMsg::FAIL]);
        $post = Yii::$app->request->post();
        //1. 给模型赋值
        $bargainProbabilityData = $post['BargainProbability'];
        $content['content'] = $post['content'];
        unset($post['BargainProbability'], $post['content']);
        $post['Event']['founder']['id'] = Yii::$app->session->get('userAuthInfo')['supplierId'];
        //判断开始时间和结束时间关系
        if ($post['Event']['startTime'] >= $post['Event']['endTime']) {
            $respMsg->return_msg = '结束时间不能早于开始时间';
            return $respMsg;
        }
        if (!Yii::$app->params['isFree']) {
            self::validityEndTime($post);
        }
        if ($event->getScenario() == 'create') {
            if (!Yii::$app->params['isFree']) {
                $pattern = self::checkPatternIsTrue($post);
                $event->pattern = $pattern['pattern'];
                $event->isShowAd = (int)$pattern['isShowAd'];
            } else {
                $event->pattern = 'freePattern';
                $event->isShowAd = (int)1;
            }
        }
        //如果是编辑
        if ($event->getScenario() == 'edit') {
            //已开始活动
            if ($event->startTime <= time()) {
                unset($post['Event']['startTime']);
            } else {//未开始活动可以编辑开始时间
                if ($event->startTime > $post['Event']['startTime']) {
                    $respMsg->return_msg = '不能设置比原本开始时间更早的时间哦~';
                    return $respMsg;
                }
            }
        }
        //Event模型数据校验
        if (!$event->load($post) || !$event->validate()) {
            Yii::warning('创建砍价活动Event模型数据校验失败：' . json_encode($event->getErrors()));
            $respMsg->return_msg = current($event->getFirstErrors());
            return $respMsg;
        }
        //1.1当是创建场景时要判断关键字的存在
        if ($event->getScenario() == 'create') {
            $keywordResult = EventFacadeApi::ChecklistKeyword($event->advancedSetting['keyword'], $event->founder['id'], 1, 'query');
            if ($keywordResult['return_code'] == RespMsg::FAIL) {
                $respMsg->return_msg = $keywordResult['return_msg'];
                return $respMsg;
            }
            if (!$keywordResult['return_msg']['status']) {
                $respMsg->return_msg = '关键字已经存在了';
                return $respMsg;
            }
        }
        //2.BargainProbability模型数据校验
        if (!$bargainProbability->load($bargainProbabilityData, '') || !$bargainProbability->validate()) {
            Yii::warning('创建砍价活动BargainProbability模型数据校验失败：' . json_encode($bargainProbability->getErrors()));
            $respMsg->return_msg = current($bargainProbability->getFirstErrors());
            return $respMsg;
        }
        //3.EventDescription模型数据校验
        if (!$eventDescription->load($content, '') || !$eventDescription->validate()) {
            Yii::warning('创建砍价活动EventDescription模型数据校验失败：' . json_encode($eventDescription->getErrors()));
            $respMsg->return_msg = current($eventDescription->getFirstErrors());
        }
        $respMsg->return_code = RespMsg::SUCCESS;
        return $respMsg;
    }

    /**
     * 创建/复制活动数据保存
     *
     * @param Event $event
     * @param BargainProbability $bargainProbability
     * @param EventDescription $eventDescription
     * @return RespMsg
     */
    public function saveCreateBargain(Event $event, BargainProbability $bargainProbability, EventDescription $eventDescription)
    {
        $respMsg = new RespMsg(['return_code' => RespMsg::FAIL]);
        //2.获取商家公众号信息
        $event->founder = Yii::$app->session->get('founder');
        //3.保存之前对一些默认数据初始化
        $event->_beforeSave();
        //创建活动时要分配一个广告位id，根据广告位获取每个活动带来的流量
        $event->adsenseId = NodeApiUtil::createAdsense($event->name);
        if ($event->insert()) {//插入数据
            //3.1 去爱豆子那里插入关键字，失败则把活动删除
            $checkCode = EventFacadeApi::ChecklistKeyword(
                $event->advancedSetting['keyword'],
                $event->founder['id'],
                $event->_id->__toString()
                , 'save'
            );
            if (!$checkCode['return_msg']['status']) {
                $event->isDeleted = 1;
                $event->update();
                $respMsg->return_msg = '未能创建成功，请重试';
                return $respMsg;
            }
            // 获取新建的活动主键
            $bargainProbability->eventId = $event->_id->__toString();
            $eventDescription->eventId = $event->_id->__toString();
            //3.2 砍价概率模型 与活动说明模型数据 插入
            if ($bargainProbability->insert() && $eventDescription->insert()) {
                self::picAndKwordOrc($event, $eventDescription, 'create');
                $respMsg->return_code = RespMsg::SUCCESS;
                $respMsg->return_msg = '创建成功';
            } else {
                $event->isDeleted = 1;
                $event->update();
                throw new SystemException('数据保存失败');
            }
        } else {
            throw new SystemException('创建失败');
        }
        return $respMsg;
    }

    /**
     * 真正保存编辑活动的方法
     *
     * @param Event $event
     * @param BargainProbability $bargainProbability
     * @param EventDescription $eventDescription
     * @return RespMsg
     */
    public function saveEditBargain(Event $event, BargainProbability $bargainProbability, EventDescription $eventDescription)
    {
        $respMsg = new RespMsg(['return_code' => RespMsg::FAIL]);

        $appInfo = self::getAppInfo($event->founder['id']);//获取商家公众号信息
        if ($appInfo->return_code != RespMsg::SUCCESS) {
            $respMsg->return_msg = '你还没有绑定公众号哦，请绑定后再来使用吧~';
            return $respMsg;
        }
        $event->founder = json_decode(json_encode($appInfo->return_msg), true);
        $event->_beforeSave();
        if ($event->update()) {//保存数据
            if ($bargainProbability->save() && $eventDescription->save()) {
                self::picAndKwordOrc($event, $eventDescription, 'editor');
                $respMsg->return_code = RespMsg::SUCCESS;
                $respMsg->return_msg = '编辑成功';
            } else {
                throw new SystemException('编辑失败');
            }
        } else {
            throw new SystemException('保存失败');
        }
        return $respMsg;
    }

    /**
     * 通过wxid获取公众号信息
     *
     * @param int $supplierId
     * @return RespMsg
     */
    public static function getAppInfo(int $supplierId)
    {
        $get = ['timestamp' => time(), 'wxid' => $supplierId, 'state' => StringUtil::genRandomStr(16)];
        $get['sign'] = (new SecurityUtil($get, Yii::$app->params['signKey']['apiSignKey']))->generateSign();
        $appInfo = HttpUtil::get(Yii::$app->params['serviceUrl']['weiXinApiDomain'] . '/facade/get-app-info', http_build_query($get));

        if ($appInfo->return_code == RespMsg::FAIL) {
            throw new SystemException('你还没有绑定公众号哦，请绑定后再来使用吧~');
        }
        return $appInfo->return_msg;
    }

    /**
     * 去idouzi 查询关键字
     * 增删改查 分别为 1 查询 2储存 3修改 4删除
     *
     * @param string $keyword
     * @param int $wxId
     * @param string $event_id
     * @param string $numeric
     * @return array json格式数据json_encode(array("status"=>1,"msg"=>'ok'));
     * ps: status = 1操作成功，0操作失败。
     */
    public static function ChecklistKeyword(string $keyword, int $wxId, string $event_id, string $numeric)
    {
        switch ($numeric) {
            case 'query':
                $numeric = 1;
                break;
            case 'save':
                $numeric = 2;
                break;
            case 'modify':
                $numeric = 3;
                break;
            case 'delete':
                $numeric = 4;
                break;
            default :
                return ['return_msg' => ['status' => 0], 'return_code' => RespMsg::FAIL];
                break;
        }
        $url = Yii::$app->params['serviceUrl']['idouziUrl'] . '/index.php?r=supplier/api/inquiryEventKeyword&wxid=' . $wxId . '&apikey=839';
        $post_data = array(
            "timestamp" => time(),
            "keyword" => $keyword,
            "numeric" => $numeric,
            "event_id" => $event_id,
            "wxid" => $wxId,
            "type" => 6,
            "_csrf" => Yii::$app->request->csrfToken,
        );
        $post_data['sign'] = (new SecurityUtil($post_data, Yii::$app->params['signKey']['iDouZiSignKey']))->generateSign();
        $res = HttpUtil::post($url, $post_data);
        return json_decode($res, true);
    }


    /**
     * 图片关键字识别
     *
     * @param Event $event
     * @param EventDescription $eventDescription
     */
    public static function picAndKwordOrc(Event $event, EventDescription $eventDescription, string $filed)
    {
        $event_url = "/mobile/index?eventId=" . $event->_id->__toString();
        //发起关键字识别
        $info['name'] = "新微砍价保存_" . $event->_id->__toString();
        $info['data'] = $event->name . " || " . $eventDescription->content;
        //添加图片地址组
        foreach ($event->adImages as $val) {//轮播图
            if (strpos($val, "http") === 0) {
                $info['imgs'][] = $val;
            }
        }
        if (strpos($event->advancedSetting['image'], "http") === 0) {//关键字回复图
            $info['imgs'][] = $event->advancedSetting['image'];
        } else {
            $info['imgs'] = array();
        }
        if (strpos($event->advancedSetting['shareImage'], "http") === 0) {//分享图片
            $info['imgs'][] = $event->advancedSetting['shareImage'];
        }
        $info['activity_url'] = DomainUtil::getTLD(
                Yii::$app->params['serviceUrl']['bargainDomain'],
                $event->founder['id']) . $event_url;
        $info['activity_id'] = $event->_id->__toString();
        $info['activity_type'] = 5;
        $info['do_type'] = 1;
        $info['wxid'] = $event->founder['id'];
        $url = Yii::$app->params['serviceUrl']['idouziUrl'] . '/index.php?r=supplier/api/moduleAccount&wxid=' .
            $event->founder['id'] . '&event_id=' . $event->_id->__toString() . '&module=new_bargain&apikey=839&module_type=' . $filed;
        HttpUtil::get($url);
        SendOrcMsg::sendEventORCMsg($info);
    }

    /**
     * 删除活动操作，逻辑删除，isDeleted=0正常，=1删除
     *
     * @param $eventId --活动的id
     * @return mixed
     */
    public static function deleteEvent(string $eventId)
    {
        $event = Event::find()->where(['_id' => $eventId])->one();
        $event->isDeleted = 1;
        $result = $event->update();
        if ($result) {
            $keyword = $event['advancedSetting']['keyword'];
            $wxId = Yii::$app->session->get('userAuthInfo')['supplierId'];
            $res = self::ChecklistKeyword($keyword, $wxId, $eventId, 'delete');
            if ($res['return_code'] == RespMsg::FAIL) {
                return false;
            }
            if ($res['return_msg']['status']) {
                Yii::warning(json_encode($res), __METHOD__);
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 关闭活动操作 将活动的截止时间修改为当前时间
     *
     * @param $eventId --活动id
     * @return bool
     */
    public static function closeEvent(string $eventId)
    {
        $event = Event::find()->where(['_id' => $eventId])->one();
        $time = time();
        $event->endTime = $time;
        $event->closeStatus = '已关闭';
        $res = $event->save();
        Yii::warning('error = ' . json_encode($event->getFirstErrors()), __METHOD__);
        if ($res) {
            return $time;
        } else {
            return false;
        }
    }

    /**
     * 商家每次进入活动列表页修改已开始的活动状态
     *
     * @param int $supplierId
     * @return bool|int
     */
    public function updateActivityStatus(int $supplierId)
    {
        //修改活动已开始但是活动状态仍是未开始的状态
        Event::updateAll(
            ['closeStatus' => '进行中'],
            ['and', ['founder.id' => $supplierId], ['closeStatus' => '未开始'], ['<=', 'startTime', time()]]
        );
        //修改活动已结束但是活动状态仍是进行中的状态
        Event::updateAll(
            ['closeStatus' => '已结束'],
            ['and', ['founder.id' => $supplierId], ['closeStatus' => '进行中'], ['<=', 'endTime', time()]]
        );
    }

    /**
     * 活动列表
     *
     * @param int $supplierId
     * @return RespMsg
     */
    public function getActivityList(int $supplierId)
    {
        //查询活动列表的数据
        $listInfo = Event::find()->select([
            '_id', 'closeStatus', 'endTime', "advancedSetting.keyword",
            'name', 'participants', 'startTime', 'resources.type'
        ])->where(['founder.id' => $supplierId, 'isDeleted' => 0]);
        //分页
        $page = new Pagination(['totalCount' => $listInfo->count(), 'pageSize' => 6]);
        $data = $listInfo->orderBy('createdTime desc')->offset($page->offset)->limit($page->limit)->asArray()->all();
        //组装数据
        $data = $this->createActivityListData($data, $supplierId);

        $patternTime = null;
        if(!Yii::$app->params['isFree']){
            //获取免费模式收费模式结束时间
            $patternTime = self::getPatternTime($supplierId);
        }

        return new RespMsg(['return_msg' => [
            'lists' => $data,
            'totalPage' => $page->getPageCount(),
            'couponEndAt' => isset($patternTime['couponEndAt']) ? $patternTime['couponEndAt'] : null,
            'coupon' => isset($patternTime['coupon']) ? $patternTime['coupon'] : null,
            'endAt' => isset($patternTime['endAt']) ? $patternTime['endAt'] : null,
            'gid' => Yii::$app->session->get('gid'),
            'freeUseEndTime' => !Yii::$app->params['isFree'] ? self::getSupplierFreeUseTime($supplierId) : null,
            'isFree' => Yii::$app->params['isFree']
        ]]);
    }

    /**
     * 活动统计列表接口
     *
     * @param $activityStatistic
     * @return RespMsg
     */
    public function getActivityStatistic($activityStatistic)
    {
        //过滤查询
        $listInfo = Bargain::find()->select([
            '_id', 'type', 'price', 'isLowestPrice', 'resourceStatus', 'contact.name',
            'contact.phone', 'contact.address', 'bargainPrice', 'resourceExplain'
        ])->where(['eventId' => $activityStatistic->eventId]);
        $listInfo->andFilterWhere(['=', 'resourceStatus', $activityStatistic->resourceStatus]);
        if (!empty($activityStatistic->startTime)) {
            $listInfo->andFilterWhere(['>=', 'startTime', intval($activityStatistic->startTime)]);
        }
        if (!empty($activityStatistic->endTime)) {
            $listInfo->andFilterWhere(['<=', 'startTime', intval($activityStatistic->endTime)]);
        }
        $listInfo->andFilterWhere(['or', ['like', 'contact.name', $activityStatistic->searchByNameOrPhone],
            ['like', 'contact.phone', $activityStatistic->searchByNameOrPhone]]);
        // 分页
        $page_size = 6;
        $page = new Pagination(['totalCount' => $listInfo->count(), 'pageSize' => $page_size]);
        $data = $listInfo->orderBy('startTime desc')->offset($page->offset)->limit($page->limit)->asArray()->all();
        //组装数据
        return $this->createActivityStaticData($data, $activityStatistic->eventId, $page->getPageCount());
    }

    /**
     * 组建活动列表返回的数据
     *
     * @param $data
     * @param $supplierId -- 微信id
     * @return mixed
     */
    private function createActivityListData($data, $supplierId)
    {
        $hostInfo = Yii::$app->request->hostInfo;
        foreach ($data as $key => $val) {
            $eventId = $data[$key]['_id']->__toString();
            $data[$key]['participants'] = Bargain::getMallBasicCount(['eventId' => $eventId]);
            $data[$key]['eventId'] = $eventId;
            $url = $hostInfo . '/mobile/index?eventId=' . $eventId;
            //组装三级域名
            $data[$key]['cpActUrl'] = DomainUtil::getTLD($url, $supplierId);
            $data[$key]['startTime'] = date('Y-m-d H:i:s', $data[$key]['startTime']);
            $data[$key]['endTime'] = date('Y-m-d H:i:s', $data[$key]['endTime']);
            $data[$key]['keyword'] = $data[$key]['advancedSetting']['keyword'];
            //资源类型（0微商城商品，1线下渠道交易商品）
            $data[$key]['type'] = $data[$key]['resources']['type'];
            unset($data[$key]['advancedSetting']);
            unset($data[$key]['resources']);
        }
        return $data;
    }

    /**
     * 组建活动统计返回的数据
     * 数据结构array('return_code' => 'SUCCESS/FAIL'，'return_msg' => array('bargainerLists' => array(),
     * 'sendGoodsUrl' => '去发货的链接','totalPage' => '总页数','type' => '资源类型(0微商城商品，1线下渠道交易商品)'))
     *
     * @param $data -- 活动统计的数据
     * @param $eventId -- 活动的id
     * @param $totalPage -- 总的页数
     * @return RespMsg
     */
    private function createActivityStaticData($data, $eventId, $totalPage)
    {
        //如果为空则直接返回数据
        if (empty($data)) {
            return new RespMsg([
                'return_msg' => [
                    'bargainerLists' => $data,
                    'totalPage' => $totalPage,
                    'sendGoodsUrl' => Yii::$app->params['serviceUrl']['sendGoodsUrl']
                ]
            ]);
        }
        foreach ($data as $key => $val) {
            //获取帮砍人数
            $barCon = BargainContribution::find()->where(['bargainId' => $data[$key]['_id']->__toString()])->count();
            //获取活动的名字
            $event = Event::find()->select(['resources.name'])->where(['_id' => $eventId])->one();
            $data[$key]['goodsName'] = $event['resources']['name'];
            $data[$key]['bargainId'] = $data[$key]['_id']->__toString();
            $data[$key]['helpBargainNum'] = $barCon;
            $data[$key]['bargainPrice'] = round($data[$key]['price'] - $data[$key]['bargainPrice'], 2);
            //有可能不存在contact，需要用户填了才有，所以需要判断一下
            if (isset($data[$key]['contact'])) {
                $data[$key]['name'] = $data[$key]['contact']['name'];
                $data[$key]['phone'] = $data[$key]['contact']['phone'];
                if (isset($data[$key]['contact']['address'])) {
                    $data[$key]['address'] = $data[$key]['contact']['address'];
                } else {
                    $data[$key]['address'] = '';
                }
                unset($data[$key]['contact']);
            } else {
                $data[$key]['name'] = '';
                $data[$key]['phone'] = '';
                $data[$key]['address'] = '';
            }
        }
        return new RespMsg([
            'return_msg' => [
                'bargainerLists' => $data,
                'totalPage' => $totalPage,
                'type' => $data[0]['type'],
                'sendGoodsUrl' => Yii::$app->params['serviceUrl']['sendGoodsUrl']
            ]
        ]);
    }

    /**
     * 获取模式结束时间列表
     *
     * @param int $supplierId 商家id
     * @return array
     */
    public static function getPatternTime($supplierId)
    {
        try {
            $get = array(
                'timestamp' => time(),
                'state' => StringUtil::genRandomStr(),
            );//拼装get参数
            $post = [
                'supplierId' => $supplierId,
                'flag' => Yii::$app->params['flag']
            ];
            $url = Yii::$app->params['serviceUrl']['MALL_URL'] . '/api/query-pattern-time?';
            $get['sign'] = (new SecurityUtil($get, Yii::$app->params['signKey']['mallSignKey']))->generateSign();
            $url .= http_build_query($get);
            $resp = json_decode(HttpUtil::simplePost($url, $post), true);
            //1.1 获取成功
            if (isset($resp['return_code']) && $resp['return_code'] === 'SUCCESS') {
                return $resp['return_msg'];
            }
            Yii::warning('模式获取失败，error=' . json_encode($resp), __METHOD__);
        } catch (\Exception $e) {
            Yii::warning('模式获取失败，error=' . json_encode($e->getMessage()), __METHOD__);
            return ['couponEndAt' => null, 'endAt' => null, 'coupon' => null];
        }

        return ['couponEndAt' => null, 'endAt' => null, 'coupon' => null];
    }

    /**
     * 判断属于哪个模式并返回该模式下的参数，免费模式默认显示广告，收费的根据用户选择保存数据
     *
     * @param $post
     * @return mixed
     * @throws \app\exceptions\SystemException
     */
    public static function checkPatternIsTrue($post)
    {
        $patternTime = self::getPatternTime(Yii::$app->session->get('userAuthInfo')['supplierId']);
        //获取免费试用结束时间
        $freeUseEndTime = self::getSupplierFreeUseTime(Yii::$app->session->get('userAuthInfo')['supplierId']);

        $endTime = date('Y-m-d', $post['Event']['endTime']);
        //判断结束时间是否在使用期间
        if (!$patternTime['couponEndAt'] && !$patternTime['endAt']) {
            if ($endTime > $freeUseEndTime) {
                throw new SystemException('活动截止时间超过有效期');
            }
            return array('isShowAd' => 1, 'pattern' => 'freePattern');
        }

        if ($patternTime['couponEndAt'] > $patternTime['endAt']) {
            if ($patternTime['couponEndAt'] < $endTime) {
                throw new SystemException('活动截止时间超过有效期');
            }

            if ($patternTime['endAt'] > $endTime) {
                $res['isShowAd'] = isset($post['Event']['isShowAd']) ? $post['Event']['isShowAd'] : 0;
                $res['pattern'] = 'chargePattern';
            } else {
                $res['isShowAd'] = 1;
                $res['pattern'] = 'freePattern';
            }
        } else {
            if ($patternTime['endAt'] < $endTime) {
                throw new SystemException('活动截止时间超过有效期');
            }
            if ($patternTime['couponEndAt'] < $endTime) {
                $res['isShowAd'] = isset($post['Event']['isShowAd']) ? $post['Event']['isShowAd'] : 0;
                $res['pattern'] = 'chargePattern';
            } else {
                $res['isShowAd'] = 1;
                $res['pattern'] = 'freePattern';
            }
        }

        return $res;
    }

    /**
     * 判断是否是收费模式
     *
     * @param $eventId
     * @return bool
     */
    public static function checkIsChargePattern($eventId)
    {
        $res = Event::getPattern($eventId);
        return $res && isset($res['pattern']) ? $res['pattern'] == 'chargePattern' : false;
    }

    /**
     * 获取商家免费试用截止时间
     *
     * @param  $supplierIds
     * @return
     * @throws SystemException
     */
    public static function getSupplierFreeUseTime($supplierId)
    {
        try {
            if (Yii::$app->session->get('freeUseTime')) {
                return Yii::$app->session->get('freeUseTime');
            }

            $get = [
                'timestamp' => time(),
                'state' => StringUtil::genRandomStr()
            ];//拼装get参数
            $post = ['supplierId' => $supplierId];
            //从广告系统拉取数据回来
            $url = Yii::$app->params['serviceUrl']['idouziUrl'] . '/supplier/api/getFreeUseTime?';
            $get['sign'] = (new SecurityUtil($get, Yii::$app->params['signKey']['iDouZiSignKey']))->generateSign();
            $url .= http_build_query($get);
            $resp = json_decode(HttpUtil::simplePost($url, $post), true);

            if (isset($resp['return_code']) && $resp['return_code'] === 'SUCCESS') {
                Yii::$app->session->set('freeUseTime', $resp['return_msg']);
                return $resp['return_msg'];
            }
        } catch (\Exception $e) {
            Yii::warning('获取免费试用时间失败，error=' . json_encode($e->getMessage()), __METHOD__);
        }

        return null;
    }

    /**
     * 校验时间
     *
     * @param $post
     * @return bool
     * @throws \app\exceptions\SystemException
     */
    public static function validityEndTime($post)
    {
        //获取两种模式的结束时间
        $patternTime = self::getPatternTime(Yii::$app->session->get('userAuthInfo')['supplierId']);
        //获取免费试用结束时间
        $freeUseEndTime = self::getSupplierFreeUseTime(Yii::$app->session->get('userAuthInfo')['supplierId']);

        $endTime = date('Y-m-d', $post['Event']['endTime']);
        //判断结束时间是否在使用期间
        if (!$patternTime['couponEndAt'] && !$patternTime['endAt']) {
            if ($endTime > $freeUseEndTime) {
                throw new SystemException('活动截止时间超过有效期');
            }
            return true;
        }
        if ($patternTime['couponEndAt'] > $patternTime['endAt']) {
            if ($patternTime['couponEndAt'] < $endTime) {
                throw new SystemException('活动截止时间超过有效期');
            }
        } else {
            if ($patternTime['endAt'] < $endTime) {
                throw new SystemException('活动截止时间超过有效期');
            }
        }

        return true;
    }

    /**
     * 处理活动默认时间
     *
     * @return bool
     */
    public static function collatingEndTime()
    {
        //获取两种模式的结束时间
        $patternTime = self::getPatternTime(Yii::$app->session->get('userAuthInfo')['supplierId']);

        return !$patternTime['couponEndAt'] && !$patternTime['endAt'];
    }

    /**
     * 获取新商城的状态
     */
    private static function getNewMallStatus()
    {
        //如果都来购升级时间已过，则默认返回1
        if (time() >= Yii::$app->params['mallEndTime']) {
            return 1;
        }
        if (($status = SessionService::getNewMallStatus()) !== null) {
            return $status;
        }
        $supplierId = Yii::$app->session->get('userAuthInfo')['supplierId'];

        //$status=0表示未有豆来购商城，$status=1表示有豆来购商城
        $status = self::requestMallSupplierGetMallStatus($supplierId);
        SessionService::setNewMallStatus($status);
        return $status;
    }

    /**
     * 请求商城子系统获取商城状态
     *
     * @param string $supplierId 商家id
     * @return mixed
     * @throws SystemException
     */
    private static function requestMallSupplierGetMallStatus(string $supplierId)
    {
        $get = ['timestamp' => time(), 'supplierId' => $supplierId, 'state' => StringUtil::genRandomStr(16)];
        $get['sign'] = (new SecurityUtil($get, Yii::$app->params['signKey']['mallSupplierSignKey']))->generateSign();
        $url = Yii::$app->params['serviceUrl']['mallSupplier'] . '/api/get-mall-status?' . http_build_query($get);
        $res = json_decode(HttpUtil::get($url), true);
        if (!isset($res['return_msg']['return_code']) || $res['return_msg']['return_code'] == RespMsg::FAIL) {
            Yii::warning('向豆来购商家运营子系统获取商城信息失败,message：' . json_encode($res), __METHOD__);
            throw new SystemException('获取数据失败，刷新重试');
        }
        return $res['return_msg']['return_msg'];
    }
}