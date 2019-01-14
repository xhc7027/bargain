<?php
namespace app\controllers\filters;

use app\commons\HttpUtil;
use app\commons\SecurityUtil;
use app\commons\StringUtil;
use app\models\Event;
use app\models\RespMsg;
use yii\base\Behavior;
use yii\web\Controller;
use Exception;
use Yii;
use yii\web\ForbiddenHttpException;

/**
 * 封号判断过滤器
 *
 * Class FreezeAccessFilter
 * @package app\controllers\filters
 */
class FreezeAccessFilter extends Behavior
{
    /**
     * 定义需要处理的action
     *
     * @var
     */
    public $actions = [];

    public function events()
    {
        return [Controller::EVENT_BEFORE_ACTION => 'beforeAction'];
    }

    public function beforeAction($event)
    {
        $actionId = $event->action->id;
        if (in_array($actionId, $this->actions)) {
            try {
                $supplierId = Event::find()->select(['founder.id'])->where(['_id' => Yii::$app->request->get('eventId')])
                    ->scalar();
                if ($this->checkFreeze($supplierId['id'])) {
                    throw new ForbiddenHttpException("该商户存在异常，活动暂时不能访问");
                }
            } catch (ForbiddenHttpException $e) {
                throw new ForbiddenHttpException($e->getMessage());
            } catch (Exception $e){
                Yii::warning('判断是否被封号异常：' . $e->getMessage(), __METHOD__);
                throw new ForbiddenHttpException("访问失败，请重试");
            }
        }
        return $event->isValid;
    }

    /**
     * 检测该商家是否被封号
     *
     * @param $supplierId
     * @throws Exception
     */
    private function checkFreeze($supplierId)
    {
        $params = ['timestamp' => time(), 'supplierId' => $supplierId, 'state' => StringUtil::genRandomStr()];
        $params['sign'] = (new SecurityUtil($params, Yii::$app->params['signKey']['ssoSignKey']))->generateSign();
        $res = json_decode(HttpUtil::get(
            Yii::$app->params['serviceUrl']['ssoDomain'] . "/sso/check-freeze.html?" . http_build_query($params)
        ), true);
        if ($res['return_code'] == RespMsg::FAIL) {
            throw new Exception($res['return_msg']);
        }
        if ($res['return_msg']['return_code'] == RespMsg::FAIL) {
            throw new Exception($res['return_msg']['return_msg']);
        }
        return $res['return_msg']['return_msg'];
    }
}