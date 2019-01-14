<?php

namespace app\components;

use Stomp\Client;
use Stomp\Exception\StompException;
use Stomp\StatefulStomp;
use yii\base\Component;
use Stomp\Transport\Message;
use Yii;
use yii\db\Exception;

/**
 * 消息队列连接客户端
 * @package app\components
 */
class ActivemqClient extends Component
{
    /**
     * @var 从配置文件中获取的多组服务地址
     */
    public $hosts;

    /**
     * @var 生产者
     */
    private $producer;

    /**
     * @return 生产者|Stomp
     * @throws Exception
     */
    public function init()
    {
        parent::init();
        if (!isset($this->hosts) || !is_array($this->hosts)) {
            throw new Exception('消息队列配置信息不完整，请检查！');
        }

        try {
            $address = '';
            foreach ($this->hosts as $host) {
                $address .= 'tcp://' . $host['host'] . ':' . $host['port'] . ',';
            }
            $address = substr($address, 0, -1);
            $this->producer = new StatefulStomp(
                new Client('failover://(' . $address . ')?randomize=false')
            );
        } catch (StompException $e) {
            Yii::error('连接ActiveMQ ' . json_encode($this->hosts) . ' 出错：' . $e->getMessage(), __METHOD__);
        }
    }

    /**
     * 发送消息
     * @param string $destination 队列名称，例如“vote.poll”
     * @param string $msg 有格式的消息内容
     * @return boolean
     */
    public function send($destination, $msg)
    {
        if (!$destination) return false;
        if (!$msg) return false;
        try {
            return $this->producer->send('/queue/' . $destination, new Message($msg), ['persistent' => 'true']);
        } catch (StompException $e) {
            Yii::error('往队列' . $destination . '中发送消息' . $msg . '失败' . $e->getMessage(), __METHOD__);
        }
        return false;
    }

}
