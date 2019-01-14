<?php

namespace app\models;

use app\services\field\FieldApi;
use app\services\handle\HandleApi;
use app\services\weixin\WeiXinService;
use yii\mongodb\ActiveRecord;
use Yii;

/**
 * 砍价集合
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $eventId
 * @property double $lowestPrice
 * @property double $price
 * @property string $type
 * @property string $openId
 * @property string $headImg
 * @property string $nickName
 * @property int $startTime
 * @property double $bargainPrice
 * @property string $isLowestPrice
 * @property int $endTime
 * @property string $resourceStatus
 * @property string $resourceExplain
 * @property mixed $contact
 * @property int $version
 * @property int $updateTime
 */
class Bargain extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function collectionName()
    {
        return 'bargain';
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return [
            '_id',
            'eventId',
            'lowestPrice',
            'price',
            'type',
            'openId',
            'headImg',
            'nickName',
            'startTime',
            'bargainPrice',
            'isLowestPrice',
            'endTime',
            'resourceStatus',
            'resourceExplain',
            'contact',
            'version',
            'updateTime',
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                ['eventId', 'lowestPrice', 'price', 'type', 'openId', 'headImg', 'nickName', 'startTime',
                    'bargainPrice', 'isLowestPrice', 'endTime', 'resourceStatus', 'resourceExplain', 'contact',
                    'version', 'updateTime'
                ], 'safe'],
            ['type', 'in', 'range' => [0, 1]],
            [['version', 'endTime', 'updateTime', 'startTime'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            '_id' => 'ID',
            'eventId' => 'Event ID',
            'lowestPrice' => 'Lowest Price',
            'price' => 'Price',
            'type' => 'Type',
            'openId' => 'Open ID',
            'headImg' => 'Head Img',
            'nickName' => 'Nick Name',
            'startTime' => 'Start Time',
            'bargainPrice' => 'Bargain Price',
            'isLowestPrice' => 'Is Lowest Price',
            'endTime' => 'End Time',
            'resourceStatus' => 'Resource Status',
            'resourceExplain' => 'Resource Explain',
            'contact' => 'Contact',
            'version' => 'Version',
            'updateTime' => 'Update Time',
        ];
    }

    /**
     * 场景设置
     * @return array
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();//本行必填，不写的话就会报如上错误
        $scenarios['needContact'] = [
            'eventId', 'lowestPrice', 'price', 'type', 'openId', 'headImg', 'nickName', 'startTime',
            'bargainPrice', 'isLowestPrice', 'endTime', 'resourceStatus', 'resourceExplain', 'contact',
            'version', 'updateTime'
        ];
        return $scenarios;
    }

    /**
     * 在业务上做字段校验
     *
     * @param null $attributeNames
     * @param bool $clearErrors
     * @return bool
     */
    public function validate($attributeNames = null, $clearErrors = true)
    {
        //1. 进行基本的校验规则
        if (!parent::validate($attributeNames, $clearErrors)) {
            return false;
        }
        //2. 如果是需要填写联系信息的场景
        if ($this->getScenario() == 'needContact') {
            $event = Yii::$app->session->get('event_' . Yii::$app->session->get('eventId'));
            //4. 自定义信息字段校验
            $customField = FieldApi::checkField($event, $this->contact);
            if ($customField->return_code == 'FAIL') {
                $this->addError('contact', '填写的联系信息不符合要求，请重新填写');
                return false;
            }
        }
        return true;
    }

    /**
     * @param array $data
     * @param null $formName
     * @return bool
     */
    public function load($data, $formName = null)
    {
        if (!parent::load($data, $formName)) {
            return false;
        }
        if ($this->getScenario() == 'needContact') {
            //将前端的数据组装成存储的格式
            $this->realContact();
        }

        return true;
    }

    /**
     * 将前端传来的格式化为数据库存储的格式
     */
    private function realContact()
    {
        /**
         * 不是需要联系信息的场景返回
         */
        if ($this->getScenario() != 'needContact') {
            return;
        }

        if (!is_array($this->contact)) {
            throw new \Exception('需要填写联系信息!');
        }
        $tmp = [];
        foreach ($this->contact as $value) {
            $tmp[$value['name']] = $value['value'];
        }
        $this->contact = $tmp;
    }

    /**
     * 在生成兑换码前，判断填写信息是否正确
     * @param Event $event
     * @param array|null $contact
     * @return RespMsg
     */
    public static function checkRule(Event $event, $contact)
    {
        //1. 如果需要填写信息
        if ($contact) {
            $customField = FieldApi::checkField($event, $contact);
            if ($customField->return_code == 'FAIL') {
                return $customField;
            }
        }
        return new RespMsg();
    }

    /**
     * 保存参加砍价时的一些基本数据初始化
     * @param Event $event 活动模型
     * @param array $attribute 可选改变字段
     */
    public function _beforeJoinSave(Event $event, $attribute = [])
    {
        if ($attribute) {
            foreach ($attribute as $key => $value) {
                $this->$key = $attribute[$key];
            }
        } else {
            $this->openId = Yii::$app->session->get('oauth_info')['openid'];
            $this->headImg = Yii::$app->session->get('oauth_info')['headimgurl'];
            $this->nickName = Yii::$app->session->get('oauth_info')['nickname'];
            $this->startTime = time();
            $this->updateTime = time();
            $this->endTime = 0;
            $this->bargainPrice = 0;
            $this->isLowestPrice = 0;
            $this->resourceStatus = $event->resources['type'] == 0 ? '正在砍' : '';
            $this->resourceExplain = '';
            $this->version = 0;
            $this->eventId = $event->_id->__toString();
            $this->lowestPrice = $event->lowestPrice;
            $this->price = $event->resources['price'];
            $this->type = $event->resources['type'];
        }
    }

    /**
     * 保存填奖信息
     * @return RespMsg
     */
    public function saveCashPrizeInfo()
    {
        $respMsg = new RespMsg();
        //1. 生成兑换码
        (new HandleApi())->createRedeemCode($this);
        if ($this->update()) {
            //减去库存
            HandleApi::changeResourcesNumber($this->eventId, 1);
            $respMsg->return_msg = $this->resourceExplain;
        } else {
            $respMsg->return_msg = '填写领奖信息有误，麻烦再试试哦~';
            $respMsg->return_code = RespMsg::FAIL;
        }
        return $respMsg;
    }

    /**
     * 参加砍价活动
     * @return RespMsg
     */
    public function join()
    {
        $respMsg = new RespMsg();
        $event = Event::findOne(Yii::$app->session->get('eventId'));
        //保存之前的默认数据填充
        $this->_beforeJoinSave($event);
        if ($this->insert()) {
            //把砍价id加入session
            Yii::$app->session->set('bargainId', $this->_id->__toString());
            $event->addParticipants();
            // 重新获取分享jsSdk配置
            return WeiXinService::returnInfoAfterJoinSave($this);
        } else {
            $respMsg->return_code = RespMsg::FAIL;
            $respMsg->return_msg = '参加这个活动的人好像有点多，请再试试哦！';
            return $respMsg;
        }
    }

    /**
     * 返回满足条件的商城基础设置条数
     *
     * @param array $where 查询条数
     * @return int
     */
    public static function getMallBasicCount(array $where)
    {
        return self::find()->select(['_id'])->where($where)->count();
    }
}