<?php

namespace console\controllers;

use common\components\kafka\Consumer;
use common\components\kafka\KafkaBehavior;

/**
 * Class NotificationController
 * @package console\controllers
 */
abstract class BaseController extends \yii\console\Controller
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
        $this->consumer->attachBehavior('kafka', KafkaBehavior::class);
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

    abstract public function getTopicName();

    abstract public function getGroupId();

    abstract public function consume($payload);
}