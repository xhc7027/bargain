<?php

namespace app\commands;


use Idouzi\Commons\FunctionUtil;
use Idouzi\Commons\HttpUtil;
use Idouzi\Commons\SecurityUtil;
use Idouzi\Commons\StringUtil;
use app\models\ActivityStatisticForm;
use app\models\Bargain;
use app\models\EventStatistics;
use app\models\QueueCreateOrder;
use app\services\data\DataFacadeApi;
use app\services\bargain\BargainStrategyApi;
use app\services\handle\HandleApi;
use Yii;
use yii\base\Exception;
use yii\console\Controller;
use app\exceptions\SystemException;
use Idouzi\Commons\QCloud\TencentQueueUtil;
use app\exceptions\MqException;
/**
 * 数据同步脚本控制器
 * 使用方法：在项目根目录下面执行
 * <code>
 * ./yii script
 * //或者是具体的某个动作
 * ./yii script index
 * </code>
 */
class ScriptController extends Controller
{
    /**
     * @var float 记录脚本开始的时间
     */
    private $timeStart = null;

    public function beforeAction($action)
    {
        $this->timeStart = time();
        Yii::trace(date('Y-m-d H:i:s', $this->timeStart) . "开始执行脚本", __METHOD__);
        return parent::beforeAction($action);
    }

    public function afterAction($action, $result)
    {
        $timeEnd = time();
        $time = $timeEnd - $this->timeStart;
        Yii::trace("{$action->id}脚本执行总耗时：{$time}秒", __METHOD__);
        return parent::afterAction($action, $result);
    }

    /**
     * 默认动作
     */
    public function actionIndex()
    {
        echo "=============================\n";
        echo "你可以在这里创建新的脚本任务，目前所支持的脚本有：\n";
        echo "检查超过一天未支付订单：add-stock-after-day\n";
        echo "主动向微商城查询未支付订单状态：update-orders-status\n";
        echo "砍价回滚库存：bargain-roll-back-stock\n";
        echo "./yii script/index 缺省处理\n";
        echo "./yii script/export-excel 脚本导出大数据到tmp目录\n";
        echo "./yii script/get-queue-load-uv-and-pv 脚本使用消息队里处理用户访问砍价时的Pv和Uv\n";
        echo "./yii script/translate-uv-data 脚本将eventUvRecord数据导出并分表\n";
        echo "=============================\n";
    }

    /**
     * 超过一天的未支付订单更改状态并且加库存
     * 每每分钟检查一次
     */
    public function actionAddStockAfterDay()
    {
        //1. 获取待付款的订单
        $bargains = Bargain::find()
            ->where(['resourceStatus' => Yii::$app->params['shopStatus']['1'], 'type' => "0"])
            ->andWhere(['$lte', 'updateTime', time() - 86400])
            ->limit(Yii::$app->params['perMinOrderCheckLimit'])
            ->all();
        //1.1 没有符合条件的订单则结束
        if (count($bargains) > 0) {
            foreach ($bargains as $bargain) {
                //如果更改库存失败，则跳过进行下一个
                if (!HandleApi::changeResourcesNumber($bargain->eventId, '1')) {
                    continue;
                }
                $bargain->resourceStatus = Yii::$app->params['shopStatus']['7'];
                //更改订单状态失败，业务补偿
                if (!$bargain->update()) {
                    HandleApi::rollbackResourcesNumber($bargain->eventId, '1');
                }
            }
        }
    }

    /**
     * 当生成了一个砍价订单后，会发给砍价系统消息，该脚本用于处理该消息队列
     */
    public function actionCreateOrder()
    {
        $queueName = Yii::$app->params['queue-mallPayment-notifyBargainCreateOrder'];
        $queue = null;
        try {
            $queues = TencentQueueUtil::batchReceiveMessage($queueName, 16);
            foreach ($queues as $queue) {
                if (!isset($queue->code) || $queue->code !== 0) {
                    TencentQueueUtil::deleteMessage($queueName, $queue->receiptHandle);
                    continue;
                }
                $msg = json_decode($queue->msgBody, true);
                //实例化队列创建订单模型
                $queueCreateOrder = new QueueCreateOrder();
                if(!$queueCreateOrder->load($msg, '') || !$queueCreateOrder->validate()){
                    throw new Exception('非法参数:' . json_encode($msg));
                }

                if ($msg['do'] != 'create') {
                    throw new Exception('参数错误:' . json_encode($msg));
                }
                //把对应信息更新到砍价信息中
                (new HandleApi)->updateShopInfo($msg);
                TencentQueueUtil::deleteMessage($queueName, $queue->receiptHandle);
            }
        } catch (Exception $e) {
            Yii::error('处理事务消息时,异常：' . $e->getMessage(), __METHOD__);
            TencentQueueUtil::deleteMessage($queueName, $queue->receiptHandle);
        }
    }

    /**
     * 每分钟去向爱豆子微商城查询订单的状态，只对状态还在未付款的15分支前的商城订单进行查询，
     * 并把新状态更新到本地更新到本地
     */
    public function actionUpdateOrdersStatus()
    {
        //1. 获取待付款的订单
        $bargains = Bargain::find()->select(['resourceExplain', '_id' => null])
            ->where(['resourceStatus' => Yii::$app->params['shopStatus']['1'], 'type' => "0"])
            ->andWhere(['$lte', 'updateTime', time() - 15 * 60])
            ->limit(Yii::$app->params['perMinOrderCheckLimit'])
            ->asArray()->all();
        //1.1 没有符合条件的订单则结束
        if (count($bargains) == 0) {
            echo 'no order need to modify its status';
            return;
        }
        //2. 去发请求
        $returnData = HandleApi::toSendForOrders($bargains);
        //2.2 根据返回数据更新订单状态
        if (!isset($returnData['return_msg']['return_code']) || $returnData['return_msg']['return_code'] != 'SUCCESS') {
            Yii::warning('update orders status failed , msg :' . json_encode($returnData), __METHOD__);
            return;
        }
        foreach ($returnData['return_msg']['return_msg'] as $order => $status) {
            //状态没有改变不用更新
            if ($status == 1 || $status == '') {
                continue;
            }
            $updateOrder = Bargain::findOne(['resourceExplain' => (string)$order]);
            //获取到了不存在的订单号,跳过并且记录在日志
            if (!$updateOrder) {
                Yii::warning('得到了不存在的订单号 , msg :' . json_encode($returnData), __METHOD__);
                continue;
            }
            //如果更改库存失败，则跳过进行下一个
            if (!HandleApi::changeResourcesNumber($updateOrder->eventId, $status)) {
                continue;
            }
            $updateOrder->resourceStatus = Yii::$app->params['shopStatus'][$status];
            //更改订单状态失败，业务补偿
            if (!$updateOrder->update()) {
                HandleApi::rollbackResourcesNumber($updateOrder->eventId, $status);
            }
        }
    }

    /**
     * 砍价回滚库存
     */
    public function actionBargainRollBackStock()
    {
        try {
            $queueName = Yii::$app->params['queueBargainRollBackStock'];
            $queue = TencentQueueUtil::batchReceiveMessage($queueName, 16);
            if (!$queue) {
                return;
            }
            $receiptHandles = [];
            foreach ($queue as $key => $val) {
                try {
                    if (!isset($queue[$key]->code) || $queue[$key]->code !== 0) {
                        $receiptHandles[] = $queue[$key]->receiptHandle;
                        continue;
                    }
                    $data = json_decode($queue[$key]->msgBody, true);
                    //数据完整性校验
                    if (!empty($data['orderId'])) {
                        HandleApi::rollbackResourcesNumberByRefund($data['orderId']);
                    }
                    $receiptHandles[] = $queue[$key]->receiptHandle;
                } catch (\TypeError $error) {
                    Yii::warning("处理消息队列里的退款成功后砍价回滚库存失败：" . $error->getMessage() . '数据：' . json_encode($val), __METHOD__);
                } catch (\Exception $e) {
                    Yii::warning("处理消息队列里的退款成功后砍价回滚库存失败：" . $e->getMessage() . '数据：' . json_encode($val), __METHOD__);
                }
            }
            //将经处理的消息队列的key删除
            if ($receiptHandles) {
                TencentQueueUtil::batchDeleteMessage($queueName, $receiptHandles);
            }
        } catch (\Exception $e) {
            Yii::warning("处理消息队列里的退款成功后砍价回滚库存失败：" . $e->getMessage() . ', ' . $e->getTraceAsString(), __METHOD__);
        }
    }

    /**
     * 脚本导出大数据到tmp目录
     */
    public function actionExportExcel()
    {
        //数据校验
        $activityStatistic = new ActivityStatisticForm();
        $activityStatistic->setScenario('getActivityExcel');
        if ($activityStatistic->load(Yii::$app->params['export'], '')) {
            $objPHPExcel = (new DataFacadeApi())->getActivityExcel($activityStatistic);
            //以文件下载的方式输出
            @ob_end_clean();//清除缓冲区,避免乱码
            $name = mb_convert_encoding('bargainExport', 'gb2312', 'UTF-8');
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $name . '.xls"');
            header('Cache-Control: max-age=0');
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $objWriter->save('/tmp/bargainExport.xls');
        }
    }

    /**
     * 脚本使用消息队里处理用户访问砍价时的Pv和Uv
     *
     * @throws SystemException
     */
    public function actionGetQueueLoadUvAndPv()
    {
        try {
            $model = TencentQueueUtil::receiveMessage(Yii::$app->params['queue-bargain-uvRecord']);

            if (isset($model->code) && $model->code === 0) {
                //业务实现
                $res = json_decode($model->msgBody, true);
                //记录活动uv
                if (BargainStrategyApi::judgeUv($res['supplierId'], $res['eventId'], $res['openId'])) {
                    EventStatistics::recordNumberOfEveryDayUv($res['eventId'], $res['supplierId']);
                }
                //记录活动pv
                EventStatistics::recordNumberOfEveryDayPv($res['eventId'], $res['supplierId']);

                //成功后删除消息
                TencentQueueUtil::deleteMessage(
                    Yii::$app->params['queue-bargain-uvRecord'],
                    $model->receiptHandle
                );
            }
        } catch (MqException $e) {
            throw new SystemException('cmq消息队列抛出异常，error:' . $e->getMessage());
        } catch (\Exception $e) {
            throw new SystemException($e->getMessage());
        }
    }


    /**
     * 脚本将eventUvRecord数据导出并分表
     */
    public static function actionTranslateUvData()
    {
        try{
            $time = Yii::$app->params['translateUvData']['time'];
            BargainStrategyApi::translateUvData($time);
        }catch (\Exception $e){
            Yii::warning('数据导出并分表出错'.$e->getMessage());
            throw new SystemException('批量查如数据，error:' . $e->getMessage());
        }
    }



}
