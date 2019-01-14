<?php
namespace app\services\field;

use app\commons\StringUtil;
use app\exceptions\SystemException;
use app\models\CustomField;
use app\models\RespMsg;
use yii\base\Exception;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/13 0013
 * Time: 上午 9:45
 */
class FieldApi implements FieldFacade
{
    /**
     * 获取自定义字段
     * @param array|string $queryId 查询的自定义id
     * @param int $noNeedRules 是否过滤rules字段，1代表是，0代表不是
     * @return array
     * @throws Exception
     * @throws SystemException
     */
    public static function getCustomField($queryId, int $noNeedRules = 1): array
    {
        $select = $noNeedRules ? ['rules' => null] : [];
        $customField = CustomField::find()->select($select)->asArray()->all();
        if (!$customField) {
            throw new SystemException('自定义字段数据没有初始化！');
        }
        if (is_array($queryId)) {//如果是数组
            foreach ($queryId as $v) {
                foreach ($customField as $key => $value) {
                    if ((string)$value['_id'] == $v) {//判断自定义字段是否被选中
                        $customField[$key]['isChosen'] = 1;
                        break;
                    }
                }
            }
        } elseif (is_string($queryId)) {//如果只是单项查询
            foreach ($customField as $key => $value) {
                if ((string)$value['_id'] == $queryId) {//判断自定义字段是否被选中
                    $customField[$key]['isChosen'] = 1;
                    break;
                }
            }
        } else {
            throw new Exception('自定义字段传入时只能是数组或者字符');
        }
        //对返回数据进行一些额外数据的添加
        foreach ($customField as &$value) {
            //确保都有isChosen下标
            $value['isChosen'] = isset($value['isChosen']) ? $value['isChosen'] : 0;
            if (isset($value['rules'])) {
                $value['length'] = $value['rules'][0]['length'];
                $value['type'] = $value['rules'][0]['type'];
                unset($value['rules']);
            }
            unset($value['_id']);
        }
        return $customField;
    }

    /**
     * 检查用户填写的字段是否符合要求
     * @param $event
     * @param $contact
     * @return RespMsg
     */
    public static function checkField($event, $contact)
    {
        $respMsg = new RespMsg();
        $customField = self::getCustomField($event['contact'], 0);
        $needAllField = 0;// 初始化活动规定的需要填写的信息数目
        //1. 遍历所有传进来的联系信息
        foreach ($contact as $key => $value) {
            $count = 0;// 初始化所有自定义字段的计数数目
            foreach ($customField as $val) {
                //2. 当匹配上时
                if ($key == $val['name']) {
                    //3. 检查规则
                    if ($key == 'phone' && !StringUtil::is_phone_num_global($value)) {
                        $respMsg->return_code = RespMsg::FAIL;
                        $respMsg->return_msg = '手机格式不对';
                        return $respMsg;
                    }
                    $result = self::fieldRuleCheck($value, $val);
                    if ($result->return_code == 'FAIL') {
                        return $result;
                    }
                    //3.1 命中了代表是商家规定的联系信息，+1
                    $needAllField++;
                    break;
                }
                $count++;
            }
            //4. 遍历了整个自定义字段都没有找到
            if ($count == count($customField)) {
                $respMsg->return_code = RespMsg::FAIL;
                $respMsg->return_msg = '填写信息与要求不符，请重试尝试';
                return $respMsg;
            }
        }
        //5. 和活动要求的所需信息数目不符
        if ($needAllField != count($event['contact'])) {
            $respMsg->return_code = RespMsg::FAIL;
            $respMsg->return_msg = '缺少需要信息，请补全信息';
        }
        return $respMsg;
    }

    /**
     * 对单个联系信息进行规制校验
     * @param $contact
     * @param array $field
     * @return RespMsg
     */
    public static function fieldRuleCheck($contact, array $field)
    {
        $function = $field['type'] == 'int' ? 'is_numeric' : 'is_' . $field['type'];
        if (!$function($contact)) {
            return new RespMsg(['return_code' => RespMsg::FAIL, 'return_msg' => $field['label'] . '填写类型错误']);
        }
        if (mb_strlen($contact, 'UTF-8') > $field['length']) {
            return new RespMsg(['return_code' => RespMsg::FAIL, 'return_msg' => $field['label'] . '长度不能超过' . $field['length']]);
        }
        return new RespMsg();
    }
}