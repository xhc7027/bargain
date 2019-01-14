<?php
namespace app\controllers\filters;

use app\commons\DomainUtil;
use yii\base\Behavior;
use yii\base\Controller;
use yii\web\ForbiddenHttpException;

/**
 * 三级域名过滤器
 *
 * Class ThemeFilter
 * @package app\controllers\filters
 */
class ThreeLevelDomainFilter extends Behavior
{
    /**
     * @var array 定义要处理的动作
     */
    public $actions;

    /**
     * @return array
     */
    public function events()
    {
        return [Controller::EVENT_BEFORE_ACTION => 'beforeAction'];
    }

    public function beforeAction($event)
    {
        //判断进入的action是否在过滤的action中，没有则不需要验证
        if (!in_array($event->action->id, $this->actions)) {
            return $event->isValid;
        }
        if (!$event->isValid = DomainUtil::checkDomain()) {
            throw new ForbiddenHttpException('访问出错，请检查连接是否正确');
        }
        return $event->isValid;
    }
}