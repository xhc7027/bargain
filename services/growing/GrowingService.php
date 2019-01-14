<?php

namespace app\services\growing;


use app\commons\HttpUtil;
use app\commons\SecurityUtil;
use Yii;

/**
 * 漏斗数据的实现
 * Class GrowingService
 * @package app\services\growing
 */
class GrowingService implements GrowingFacade
{
    /**
     * @var string 项目id
     */
    private $projectKeyId = '9c6fead577bbabb2';

    /**
     * 秘钥
     * @var string
     */
    private $secretKey = 'f156124142564dad8298b482777dfd0f';

    /**
     * 向某个服务获取对应的growing数据
     * @param string $url
     * @param int $wxId
     * @return array
     */
    public static function getServiceGrowing($url, $wxId){
        $getRequest = array('wxid' => $wxId, 'timestamp' => time(), 'outer' => 1);
        //爱豆子 参数签名
        $getRequest['sign'] = ((new SecurityUtil($getRequest, Yii::$app->params['signKey']['voteSignKey'])))
                                ->generateSign();
        $url .= http_build_query($getRequest);
        $data = json_decode(HttpUtil::get($url), true);
        if (isset($data['return_msg']['status']) && $data['return_msg']['status'] != 0) {//请求数据成功
            foreach ($data['return_msg']['data'] as $key => $val) {
                $data[$key] = $val == '无' ? '无' : $val;
            }
        }
        return $data;
    }

    /**
     * 组装cs11~cs16数据
     * @param array $data
     * @return mixed
     */
    public static function returnGrowingData(array  $data)
    {
        $postData['cs1'] = isset($postData['cs1']) ? $postData['cs1'] : (isset($data['id']) ? $data['id'] : '无');

        for ($i = 11; $i <= 16; $i++) {
            $key = 'cs' . $i;
            $postData[$key] = isset($data[$key]) ? $data[$key] : '无';
        }
        $postData['cs11'] = $postData['cs11'] == '无' ? (isset($data['mobi']) ? $data['mobi'] : '无') : $postData['cs11'];
        return $postData;
    }


    /**
     * 请求growingIo
     * @param $url
     * @param string $method
     * @param null $postfields
     * @param array $headers
     * @return mixed
     */
    public static function httpToGrowing($url, $method = 'GET', $postfields = null, $headers = array())
    {
        $ci = curl_init();
        curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ci, CURLOPT_USERAGENT, '127.0.0.1');
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ci, CURLOPT_TIMEOUT, 30);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ci, CURLOPT_ENCODING, "");
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ci, CURLOPT_HEADER, false);
        switch ($method) {
            case 'POST':
                curl_setopt($ci, CURLOPT_POST, TRUE);
                if (!empty($postfields)) {
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
                }
                break;
            case 'DELETE':
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (!empty($postfields)) {
                    $url = "{$url}?{$postfields}";
                }
        }
        curl_setopt($ci, CURLOPT_URL, $url);
        curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ci);
        curl_close($ci);
        return $response;
    }

    /**
     * 漏斗数据token生成
     * @param $keyArray
     * @param string $projectKeyId
     * @param string $secretKey
     * @return string
     */
    public function authToken($keyArray)
    {
        $message = "ai=" . $this->projectKeyId . "&cs=" . $keyArray;
        return hash_hmac('sha256', $message, $this->secretKey, false);
    }
}