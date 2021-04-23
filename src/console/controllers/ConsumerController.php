<?php

namespace yii\kafka\console\controllers;

use common\components\kafka\Consumer;
use common\components\kafka\KafkaBehavior;

/**
 * Class DemoController
 * @package console\controllers
 */
abstract class ConsumerController extends \yii\console\Controller
{
    /**
     * @var Consumer
     */
    public $consumer;

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();
        $this->consumer = \Yii::createObject(Consumer::class, [
            $this->getGroupId(),
            $this->getTopicName()
        ]);
    }

    /**
     * @throws \Exception
     */
    public function actionConsume()
    {
        $topic = $this->consumer->newTopic();
        $topic->consumeStart(0, RD_KAFKA_OFFSET_STORED);
        while (true) {
            $message = $topic->consume(0, 120*10000);
            if (empty($message)) {
                continue;
            }
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    $this->consumer->consumer(function ($payload) {
                        $this->consume($payload);
                    }, $message->payload);
                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    echo "No more messages; will wait for more\n";
                    break;
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    echo "Timed out\n";
                    break;
                default:
                    throw new \Exception($message->errstr(), $message->err);
                    break;
            }
        }
    }

    /**
     * 开启新消费者需要重新定义主题名称
     * @return string 主题名称
     */
    abstract public function getTopicName();

    /**
     * 开启新消费者需要重新定义分组id
     * @return string 分组id
     */
    abstract public function getGroupId();

    /**
     * 消费者实际执行业务代码的方法
     * @param $payload
     * @return mixed
     */
    abstract public function consume($payload);
}