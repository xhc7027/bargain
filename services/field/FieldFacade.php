<?php

namespace app\services\field;

/**
 * 定义自定义字段管理模块对外接口
 */
interface FieldFacade
{
    /**
     * 获取自定义字段
     * @param array|string $queryId
     * @param int $noNeedRules 是否过滤rules字段，1代表是，0代表不是
     * @return array
     * @throws Exception
     */
    public static function getCustomField($queryId, int $noNeedRules = 1): array;

    /**
     * 检查用户填写的字段是否符合要求
     * @param $event
     * @param $contact
     * @return RespMsg
     */
    public static function checkField($event, $contact);
}