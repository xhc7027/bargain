<?php

namespace app\tests\unit\services;


use app\commons\RememberUserUtil;
use app\services\TaskService;
use Codeception\Test\Unit;
use Yii;

class RememberUserTest extends Unit
{
    protected function setUp()
    {
        return parent::setUp(); // TODO: Change the autogenerated stub
    }

    /**
     * 正确的信息
     */
    public function testJudgeUserInfo()
    {
        $data = [
            'username' => 15820492387,
            'password' => 123456,
            'rememberMe' => "1",
            'lastLogin' => time(),
        ];
        $return = null;
        //调取JWT模型
        $header = base64_encode(json_encode(["alg" => "HS256", "typ" => "JWT"]));
        $payLoad = base64_encode(json_encode($data));
        $sign = '1USyZ9Adxx8lI58hsikLbnBZpMEGn4gA';
        $token = hash("sha256", $header . $payLoad . $sign);
        $cookies = $header . "." . $payLoad . "." . $token;

        $tickets = RememberUserUtil::judgeUserInfo($cookies);
        if(!$tickets)
        {
            $return = false;
        }else{
            $return = true;
        }
        $this->assertEquals(true, $return);
    }


    /**
     * 超过时间
     */

    public function testJudgeUserInfoFalse()
    {
        $data = [
            'username' => 15820492387,
            'password' => 123456,
            'rememberMe' => "1",
            'lastLogin' => 1530374400,
        ];
        $return = null;
        //调取JWT模型
        $header = base64_encode(json_encode(["alg" => "HS256", "typ" => "JWT"]));
        $payLoad = base64_encode(json_encode($data));
        $sign = '1USyZ9Adxx8lI58hsikLbnBZpMEGn4gA';
        $token = hash("sha256", $header . $payLoad . $sign);
        $cookies = $header . "." . $payLoad . "." . $token;

        $tickets = RememberUserUtil::judgeUserInfo($cookies);
        if(!$tickets)
        {
            $return = false;
        }else{
            $return = true;
        }

        $this->assertEquals(true, !$return);
    }

    /**
     * 账号密码错误
     */
    public function testJudgeUserInfoFalseTwo()
    {
        $data = [
            'username' => 156654571,
            'password' => 123456,
            'rememberMe' => "1",
            'lastLogin' => 1531048414,
        ];
        $return = null;
        //调取JWT模型
        $header = base64_encode(json_encode(["alg" => "HS256", "typ" => "JWT"]));
        $payLoad = base64_encode(json_encode($data));
        $sign = '1USyZ9Adxx8lI58hsikLbnBZpMEGn4gA';
        $token = hash("sha256", $header . $payLoad . $sign);
        $cookies = $header . "." . $payLoad . "." . $token;

        $tickets = RememberUserUtil::judgeUserInfo($cookies);
        if(!$tickets)
        {
            $return = false;
        }else{
            $return = true;
        }

        $this->assertEquals(true, !$return);
    }
}