<?php

namespace app\models;

use yii\base\Exception;
use yii\mongodb\ActiveRecord;
use Yii;

/**
 * 活动集合
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string                        $name
 * @property string                        $organizer
 * @property int                           $startTime
 * @property int                           $endTime
 * @property int                           $participants
 * @property int                           $virtualParticipants
 * @property int                           $pv
 * @property double                        $lowestPrice
 * @property int                           $createdTime
 * @property int                           $updatedTime
 * @property array                         $adImages
 * @property string                        $adLink
 * @property int                           $acquisitionTiming
 * @property array                         $contact
 * @property EventFounder                  $founder
 * @property EventAdvancedSetting          $advancedSetting
 * @property EventResources                $resources
 * @property int                           $isDeleted
 * @property int                           $shareType
 * @property int                           $isShowAd
 * @property string                        $pattern
 * @property string                        $closeStatus
 */
class Event extends ActiveRecord
{
    const NAMESPACE = __NAMESPACE__;

    /**
     * @inheritdoc
     */
    public static function collectionName()
    {
        return 'event';
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return [
            '_id',
            'name',
            'organizer',
            'startTime',
            'endTime',
            'participants',
            'virtualParticipants',
            'pv',
            'lowestPrice',
            'createdTime',
            'updatedTime',
            'adImages',
            'adLink',
            'acquisitionTiming',
            'contact',
            'founder',
            'advancedSetting',
            'resources',
            'isDeleted',
            'shareType',
            'closeStatus',
            'isShowAd',
            'pattern',
            'adsenseId'
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                [
                    'name', 'organizer', 'startTime', 'endTime', 'participants', 'virtualParticipants', 'pv',
                    'lowestPrice', 'createTime', 'updatedTime', 'adImages', 'adLink', 'acquisitionTiming', 'contact',
                    'founder', 'advancedSetting', 'resources', 'isDeleted', 'shareType', 'closeStatus', 'isShowAd',
                    'pattern', 'adsenseId'
                ], 'safe'
            ],
            [['startTime', 'endTime', 'participants', 'virtualParticipants', 'pv', 'isDeleted', 'shareType', 'isShowAd'], 'integer'],
            [['lowestPrice'], 'double', 'max' => 99999999],
            ['isDeleted', 'in', 'range' => [0, 1]],
            ['shareType', 'in', 'range' => [0, 1]],
            [['name'], 'string', 'length' => [1, 30], 'message' => '商品名称不能超过30个字'],
            [['organizer'], 'string', 'length' => [1, 15], 'message' => ' 活动单位不能超过15个字'],
            [['pattern'], 'string'],
            [
                [
                    'name', 'organizer', 'startTime', 'endTime', 'participants', 'virtualParticipants', 'pv',
                    'lowestPrice', 'createTime', 'updatedTime', 'adImages', 'acquisitionTiming', 'contact',
                    'founder', 'advancedSetting', 'shareType'
                ],
                'required', 'on' => ['create', 'edit']
            ],
            [['resources'], 'required', 'on' => ['create']],
        ];
    }

    /**
     * 场景设置
     *
     * @return array
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();//本行必填，不写的话就会报如上错误
        $scenarios['create'] = [
            'name', 'organizer', 'startTime', 'endTime', 'lowestPrice',
            'adImages', 'adLink', 'acquisitionTiming', 'contact', 'founder',
            'advancedSetting', 'resources', 'adsenseId'
        ];
        $scenarios['edit'] = [
            'name', 'organizer', 'endTime', 'startTime',
            'adImages', 'adLink', 'acquisitionTiming', 'contact', 'founder',
            'advancedSetting'
        ];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            '_id' => 'ID',
            'name' => 'Name',
            'organizer' => 'Organizer',
            'startTime' => 'Start Time',
            'endTime' => 'End Time',
            'participants' => 'Participants',
            'virtualParticipants' => 'Virtual Participants',
            'pv' => 'Pv',
            'lowestPrice' => 'Lowest Price',
            'createdTime' => 'Created Time',
            'updatedTime' => 'Updated Time',
            'adImages' => 'Ad Images',
            'adLink' => 'Ad Link',
            'acquisitionTiming' => 'Acquisition Timing',
            'contact' => 'Contact',
            'founder' => 'Founder',
            'advancedSetting' => 'AdvancedSetting',
            'resources' => 'Resources',
            'isDeleted' => 'Is Deleted',
            'shareType' => 'share Type',
            'closeStatus' => 'Close Status',
            'pattern' => 'Pattern',
            'isShowAd' => 'Is Show Ad',
            'adsenseId' => 'Adsense Id'
        ];
    }

    /**
     * 对load方法做了一些自己的业务处理
     *
     * @param array $data
     * @param null  $formName
     * @return bool
     */
    public function load($data, $formName = null)
    {
        if ($this->getScenario() == 'edit') {
            if (isset($data['Event']['endTime']) && $data['Event']['endTime'] < $this->endTime) {
                $this->addError('endTime', '结束时间不能小于设置时间');
                return false;
            }
        }
        if (!parent::load($data, $formName)) {
            $this->addError('load', current(parent::getFirstErrors()));
            return false;
        }
        $this->contact = BargainContact::nameTransferId($this->contact);
        return true;
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
        //如果字段验证正确再判断业务上的规则
        if (parent::validate($attributeNames, $clearErrors)) {
            if (!is_array($this->adImages)) {
                $this->addError('adImages', '至少要上传一张轮播图.');
                return false;
            }
            $scenario = $this->getScenario();
            if ($this->lowestPrice <= 0) {
                $this->addError('lowestPrice', '最低价一定要大于零哦~');
                return false;
            }
            //高级设置模型校验
            $model = new EventAdvancedSetting;
            $model->setScenario($scenario);
            if (!$model->load($this->advancedSetting, '') || !$model->validate()) {
                $this->addError('advancedSetting', current($model->getFirstErrors()));
                return false;
            }
            // 商家信息模型校验
            $model = new EventFounder;
            $model->setScenario($scenario);
            if (!$model->load($this->founder, '') || !$model->validate()) {
                $this->addError('founder', current($model->getFirstErrors()));
                return false;
            }
            //商品信息模型校验
            $model = new EventResources;
            $model->setScenario($scenario);
            if (!$model->load($this->resources, '') || !$model->validate()) {
                $this->addError('resources', current($model->getFirstErrors()));
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * 保存之前的某些数据处理
     */
    public function _beforeSave()
    {
        if ($this->getScenario() == 'create') {
            $this->participants = 0;
            $this->virtualParticipants = 0;
            $this->pv = 0;
            $this->createdTime = time();
            $this->isDeleted = 0;
            $resources = $this->resources;
            $resources['number'] = (int)$resources['number'];
            $this->resources = $resources;
        }
        $this->isDeleted = 0;
        $this->updatedTime = time();
        $advancedSetting = $this->advancedSetting;
        $advancedSetting['shareType'] = isset($this->advancedSetting['shareType']) ? (int)$this->advancedSetting['shareType'] : 0;
        $this->advancedSetting = $advancedSetting;
        $this->startTime = (int)$this->startTime;
        $this->endTime = (int)$this->endTime;
        $founder = $this->founder;
        $founder['id'] = (int)$this->founder['id'];
        $this->founder = $founder;
        if ($this->closeStatus != '已关闭') {
            $this->closeStatus = $this->startTime > time() ? '未开始' : ($this->endTime < time() ? '已结束' : '进行中');
        }
    }

    /**
     * 设置event表的静态数据
     *
     * @return bool
     */
    public function setStaticEventData()
    {
        $eventId = $this->_id->__toString();
        Yii::$app->session['eventId'] = $eventId;
        //判断session是否存在活动数据
        if (!isset(Yii::$app->session['event_' . $eventId]) || count(Yii::$app->session['event_' . $eventId]) == 0) {
            $event = Event::find()->select(['virtualParticipants' => null, 'adImages' => null,
                'pv' => null, 'participants' => null, 'endTime' => null, 'adLink' => null, 'startTime' => null
            ])
                ->where(['_id' => $eventId, 'isDeleted' => 0])->asArray()->one();//实时数据不去session取
            if (!$event) {
                return false;
            }
            $event['_id'] = (string)$event['_id'];
            Yii::$app->session->set('event_' . $eventId, $event);
        }
        return true;
    }

    /**
     * 参与人数自增
     */
    public function addParticipants()
    {
        $this->participants++;
        $this->virtualParticipants++;
        $this->update();
    }

    /**
     * pv自增
     */
    public function addPv()
    {
        $this->pv++;
        $this->update();
    }

    /**
     * 获取单个的活动信息
     *
     * @param $condition array 查询条件
     * @param $field array 查询字段
     * @return array|null|ActiveRecord
     */
    public static function getEventInfoOne($condition, $field)
    {
        return self::find()->select($field)->where($condition)->asArray()->one();
    }

    /**
     * 获取活动模式
     *
     * @param $id
     * @return array|null|\yii\mongodb\ActiveRecord
     */
    public static function getPattern($id)
    {
        return self::find()->select(['pattern'])->where(['_id' => $id])->asArray()->one();
    }
}