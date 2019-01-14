<?php

namespace app\controllers;

use app\commons\TencentPicUtil;
use app\controllers\filters\ModulesAccessFilter;
use app\controllers\filters\RequestDataFilter;
use app\controllers\filters\SupplierAccessFilter;
use app\models\BargainProbability;
use app\controllers\filters\EventInfoFilter;
use app\models\EventDescription;
use app\models\RespMsg;
use app\models\ActivityStatisticForm;
use app\services\bargain\BargainStrategyApi;
use app\services\data\DataFacadeApi;
use app\services\handle\HandleApi;
use yii\web\Controller;
use app\models\Event;
use app\models\Bargain;
use app\services\event\EventFacadeApi;
use yii;
use app\services\image\ImageService;

/**
 * 商家运营后台请求控制器
 *
 * @package app\controllers
 */
class SupplierController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => SupplierAccessFilter::className(),
            ],
            'mallAuthAccess' => [
                'class' => ModulesAccessFilter::className(),
                'actions' => ['bargain', 'get-bargain-data', 'save-bargain', 'check-keywords', 'get-shop-info', 'index']
            ],
            'EventInfoFilter' => [
                'class' => EventInfoFilter::className(),
                'actions' => ['close', 'delete', 'cash-prize']
            ],
            'requestDataFilter' => [
                'class' => RequestDataFilter::className(),
            ]
        ];
    }

    /**
     * 首页
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * 商家活动创建编辑页面
     */
    public function actionBargain()
    {
        return $this->render('bargain');
    }

    /**
     * 商家后台活动页面数据获取
     */
    public function actionGetBargainData()
    {
        $respMsg = new RespMsg(['return_code' => RespMsg::FAIL]);
        try {
            $eventId = Yii::$app->request->get('eventId');
            $from = Yii::$app->request->get('from');
            //如果不是创建编辑删除其中一个行为
            if (!in_array($from, Yii::$app->params['supplierBargainAction'])) {
                Yii::warning('from参数错误 : ' . $from, __METHOD__);
                $respMsg->return_msg = '访问出错';
                return $respMsg;
            }
            //只有创建的活动可以不传活动id
            if (empty($eventId) && $from != 'create') {
                Yii::warning('bargainId参数错误 : ' . $eventId, __METHOD__);
                $respMsg->return_msg = '非法参数';
                return $respMsg;
            }
            $data = (new EventFacadeApi())->getBargainData($eventId, $from);
            return $data;
        } catch (\Exception $e) {
            $respMsg->return_msg = $e->getMessage();
            return $respMsg;
        }
    }

    /**
     * 保存商家砍价信息的对外接口
     */
    public function actionSaveBargain()
    {
        $respMsg = new RespMsg(['return_code' => RespMsg::FAIL]);
        if (!in_array($from = strtolower(Yii::$app->request->get('from')), Yii::$app->params['supplierBargainAction'])) {
            return new RespMsg(['return_code' => RespMsg::FAIL, 'return_msg' => '保存失败，请查看链接是否正确']);
        }
        $eventId = Yii::$app->request->get('eventId');
        if ($from != 'create' && !$eventId) {//不是创建都需要活动id
            return new RespMsg(['return_code' => RespMsg::FAIL, 'return_msg' => '保存失败，请查看链接是否正确']);
        }
        $appInfo = EventFacadeApi::getAppInfo(Yii::$app->session->get('userAuthInfo')['supplierId']);
        $founder = json_decode(json_encode($appInfo->return_msg), true);
        //判断是否有公众号信息
        if (!(isset($founder['appId']) && $founder['appId'] != '')) {
            $respMsg->return_msg = '客官，去首页绑定公众号才能创建或者编辑活动哦！';
            return $respMsg;
        }
        Yii::$app->session->set('founder', $founder);
        //进行数据校验

        if ($from == 'edit') {//如果是编辑活动
            $event = Event::findOne(['_id' => $eventId, 'isDeleted' => 0]);
            $event->setScenario('edit');
            $bargainProbability = BargainProbability::findOne(['eventId' => $eventId]);
            $eventDescription = EventDescription::findOne(['eventId' => $eventId]);
            if (!$event || !$bargainProbability || !$eventDescription) {
                $respMsg->return_msg = '活动数据有误，请重试';
                return $respMsg;
            }
        } else {
            //复制活动
            if ($from == 'copy') {
                //判断是否有这个活动
                if (!Event::find()->where(['_id' => $eventId, 'isDeleted' => 0])->count()) {
                    $respMsg->return_msg = '活动数据有误，请重试';
                    return $respMsg;
                }
            }
            $event = new Event();
            $event->setScenario('create');
            $bargainProbability = new BargainProbability();
            $eventDescription = new EventDescription();
        }
        try {
            //1.校验参数
            $validate = EventFacadeApi::validate($event, $bargainProbability, $eventDescription);
            if ($validate->return_code == RespMsg::FAIL) {
                return $validate;
            }
            //进行保存数据
            $functionName = $from == 'edit' ? 'saveEditBargain' : 'saveCreateBargain';
            return (new EventFacadeApi())->$functionName($event, $bargainProbability, $eventDescription);
        } catch (\Exception $e) {
            $respMsg->return_msg = $e->getMessage();
            return $respMsg;
        }
    }

    /**
     * 用于异步查询关键字是否存在
     * @param $keyword
     * @return RespMsg
     */
    public function actionCheckKeyword($keyword)
    {
        $respMsg = new RespMsg(['return_code' => RespMsg::FAIL]);
        $supplierId = Yii::$app->session->get('userAuthInfo')['supplierId'];
        $keywordResult = EventFacadeApi::ChecklistKeyword($keyword, $supplierId, 1, 'query');
        if ($keywordResult['return_code'] == RespMsg::FAIL) {
            $respMsg->return_msg = $keywordResult['return_msg'];
            return $respMsg;
        }
        if (!$keywordResult['return_msg']['status']) {
            $respMsg->return_msg = '关键字已经存在了';
            return $respMsg;
        }
        $respMsg->return_code = RespMsg::SUCCESS;
        return $respMsg;
    }

    /**
     * 兑奖
     * @param  $bargainId --参与者的id
     * @return RespMsg
     * 数据结构 array('return_code' => 'SUCCESS/FAIL'，'return_msg' => 'msg')
     */
    public function actionCashPrize($bargainId)
    {
        $respMsg = new RespMsg(['return_code' => RespMsg::FAIL]);
        $bargain = Bargain::find()->select(['resourceStatus', 'resourceExplain', 'type'])
            ->where(['_id' => $bargainId])->one();
        //判断是否是微商城商品
        if ($bargain['type'] == 1) {
            //判断兑奖码是否存在
            if (empty($bargain['resourceExplain'])) {
                $respMsg->return_msg = '兑奖码不存在';
                return $respMsg;
            }
            //判断是否已经兑奖
            if ($bargain['resourceStatus'] == '已兑奖') {
                $respMsg->return_msg = '该兑奖码已兑换';
                return $respMsg;
            }
            $result = HandleApi::doCashPrize($bargainId);
            if ($result) {
                $respMsg->return_code = RespMsg::SUCCESS;
                $respMsg->return_msg = '兑换成功';
                return $respMsg;
            } else {
                $respMsg->return_msg = '兑换失败';
                return $respMsg;
            }
        }
        $respMsg->return_msg = '只有非微商城商品才能兑换';
        return $respMsg;
    }

    /**
     * 砍价商家后台活动关闭接口
     * @param $eventId
     * @return RespMsg
     * 数据结构 array('return_code' => 'SUCCESS/FAIL'，'return_msg' => 'msg')
     */
    public function actionClose($eventId)
    {
        $respMsg = new RespMsg(['return_code' => RespMsg::FAIL]);
        //判断是否传入bargainId
        $result = EventFacadeApi::closeEvent($eventId);
        if ($result) {
            $respMsg->return_code = RespMsg::SUCCESS;
            $respMsg->return_msg = date("Y-m-d H:i:s", $result);
        } else {
            $respMsg->return_msg = '关闭失败';
        }
        return $respMsg;
    }

    /**
     * 砍价商家后台活动删除接口，逻辑删除
     * @param $eventId --活动的id
     * @return RespMsg
     * 数据结构 array('return_code' => 'SUCCESS/FAIL'，'return_msg' => 'msg')
     */
    public function actionDelete($eventId)
    {
        $respMsg = new RespMsg(['return_code' => RespMsg::FAIL]);
        //进行删除操作
        $result = EventFacadeApi::deleteEvent($eventId);
        if ($result) {
            $respMsg->return_code = RespMsg::SUCCESS;
            $respMsg->return_msg = '删除成功';
        } else {
            $respMsg->return_msg = '删除失败';
        }
        return $respMsg;
    }

    /**
     * 获取砍价商家后台活动列表
     * @return RespMsg
     * 数据结构array('return_code' => 'SUCCESS/FAIL'，'return_msg' => array('lists' => array(),'totalPage' => '总页数'))
     * lists  具体活动列表数据
     */
    public function actionList()
    {
        //获取微信id
        $supplierId = Yii::$app->session->get('userAuthInfo')['supplierId'];
        if (empty($supplierId)) {
            return new RespMsg(['return_code' => RespMsg::FAIL, 'return_msg' => '参数不能为空']);
        }
        (new EventFacadeApi())->updateActivityStatus($supplierId);
        return (new EventFacadeApi())->getActivityList($supplierId);
    }

    /**
     * 活动统计
     * @return RespMsg
     * 数据结构array('return_code' => 'SUCCESS/FAIL'，'return_msg' => array('bargainerLists' => array(),
     * 'sendGoodsUrl' => '去发货的链接','totalPage' => '总页数','type' => '资源类型(0微商城商品，1线下渠道交易商品)'))
     * bargainerLists  参与人的信息
     */
    public function actionActivityStatistic()
    {
        //数据校验
        $activityStatistic = new ActivityStatisticForm();
        $activityStatistic->setScenario('getActivityStatistic');
        if ($activityStatistic->load(Yii::$app->request->post(), '')) {
            return (new EventFacadeApi())->getActivityStatistic($activityStatistic);
        }
        return new RespMsg(['return_code' => RespMsg::FAIL, 'return_msg' => '参数不能为空']);
    }

    /**
     * 活动列表页导出excel
     * @return RespMsg
     */
    public function actionActivityExcel()
    {
        //数据校验
        $activityStatistic = new ActivityStatisticForm();
        $activityStatistic->setScenario('getActivityExcel');
        if ($activityStatistic->load(Yii::$app->request->get(), '')) {
            $objPHPExcel = (new DataFacadeApi())->getActivityExcel($activityStatistic);
            //以文件下载的方式输出
            ob_end_clean();//清除缓冲区,避免乱码
            $name = mb_convert_encoding('微砍价活动数据', 'gb2312', 'UTF-8');
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $name . '.xls"');
            header('Cache-Control: max-age=0');
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $objWriter->save('php://output');
        }
        return new RespMsg(['return_code' => RespMsg::FAIL, 'return_msg' => '参数不能为空']);
    }


    /**
     * 用于获取商品列表
     * @return RespMsg
     */
    public function actionGetShopInfo()
    {
        $respMsg = new RespMsg(['return_code' => RespMsg::FAIL]);
        try {
            $supplierId = Yii::$app->session->get('userAuthInfo')['supplierId'];
            $params = ['wxid' => $supplierId, 'apikey' => 839];//用于存储get参数
            $params['p'] = Yii::$app->request->get('page', 1);
            if (Yii::$app->request->get('type')) {//商品分类id
                $params['cat_id'] = (int)Yii::$app->request->get('type');
            }
            if (Yii::$app->request->get('goodsSearch', '')) {//	搜索商品名称或者id
                $params['keyword'] = Yii::$app->request->get('goodsSearch');
            }
            return (new HandleApi())->getShopInfo($params);
        } catch (\Exception $e) {
            $respMsg->return_msg = $e->getMessage();
            return $respMsg;
        }

    }

    /**
     * cos签名获取
     * 格式如下
     * return_msg:
     * {
     *  fileid:"20170328204517_42352"
     *  sign:"ovqx0FsGgU3Bozg0Yy7HVIpfvtRhPTEwMDEwOTMyJmI9emxrdGVzdCZrPUFLSURqYTUwakRySWtqSEhyeENpV2JLWkpwYjlSS2xPY"
     *  url:"http://web.image.myqcloud.com/photos/v2/10010932/zlktest/0/20170328204517_42352"
     * }
     **/
    public function actionGetCosSign()
    {
        return TencentPicUtil::getCosSign();
    }

    /**
     * 获取砍价的刀数
     * @return RespMsg
     */
    public function actionGetBargainCalcul()
    {
        $respMsg = new RespMsg();
        $bargainProbabilityData = Yii::$app->request->post('bargainProbability');
        $price = Yii::$app->request->post('price');
        $lowestPrice = Yii::$app->request->post('lowestPrice');
        if (!$bargainProbabilityData || !$price || !$lowestPrice) {//基本post参数校验
            $respMsg->return_code = RespMsg::FAIL;
            $respMsg->return_msg = '非法参数';
            return $respMsg;
        }
        $bargainProbability = new BargainProbability();
        //先对数据模型做校验
        if (!$bargainProbability->load($bargainProbabilityData, '') || !$bargainProbability->validate()) {
            $respMsg->return_code = RespMsg::FAIL;
            $respMsg->return_msg = current($bargainProbability->getFirstErrors());
            return $respMsg;
        }
        // 获取刀数,校验通过就能保证数据返回正确
        $respMsg->return_msg = BargainStrategyApi::calculateBargainTimes($bargainProbabilityData, $price, $lowestPrice);
        return $respMsg;
    }

    /**
     * 用户退出，然后跳回爱豆子退出页
     */
    public function actionLogout()
    {
        Yii::$app->session->destroy();
        $this->redirect(Yii::$app->params['serviceUrl']['idouziUrl'] . '/supplier/user/logout');
    }

    /**
     * 上传图片
     * 返回图片在万象优图的访问路径
     * @return RespMsg
     * }
     */

    public function actionFile()
    {
        $respMsg = new RespMsg();
        $image = isset($_FILES['file']) ? $_FILES['file'] : null;
        $error = isset($image['error']) ? $image['error'] : null;
        if ($error !== 0 || empty($image)) {
            Yii::warning('上传图片到后台失败', __METHOD__);
            $respMsg->return_code = RespMsg::FAIL;
            $respMsg->return_msg = '上传图片到后台失败';
            return $respMsg;
        }
        $path = ImageService::uploadImage($image);
        if ($path) {
            $respMsg->return_msg = $path;
            return $respMsg;
        } else {
            Yii::warning('上传图片到后台失败', __METHOD__);
            $respMsg->return_code = RespMsg::FAIL;
            $respMsg->return_msg = '上传图片到后台失败';
            return $respMsg;
        }
    }

    /**
     * 用于获取商品列表
     *
     * @return RespMsg
     */
    public function actionGetCoupon()
    {
        $respMsg = new RespMsg();

        try {
            $respMsg->return_msg = HandleApi::getSupplierCoupon(Yii::$app->session->get('userAuthInfo')['supplierId']);
        } catch (\Exception $e) {
            $respMsg->return_msg = $e->getMessage();
            $respMsg->return_code = RespMsg::FAIL;
        }

        return $respMsg;
    }

    /**
     * 获取用户未查看的优惠券的数据
     *
     * @return RespMsg
     */
    public function actionQueryNoReadNum()
    {
        $respMsg = new RespMsg();

        try {
            $respMsg->return_msg = HandleApi::queryNoReadCount(Yii::$app->session->get('userAuthInfo')['supplierId']);
        } catch (\Exception $e) {
            $respMsg->return_msg = $e->getMessage();
            $respMsg->return_code = RespMsg::FAIL;
        }

        return $respMsg;
    }
}