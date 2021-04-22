<?php
namespace yii\kafka;

use yii\helpers\Json;

/**
 * Class Producer
 * @package common\components\kafka
 */
class Producer extends Queue
{
    /**
     * @var \RdKafka\Producer
     */
    protected $producer;

    /**
     * Producer constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->setProducer();
        parent::__construct($config);
    }

    public function setProducer()
    {
        $conf = new \RdKafka\Conf();
        $conf->set('metadata.broker.list', $this->getConfig('broker_list'));
        $this->producer = new \RdKafka\Producer($conf);
    }

    /**
     * @return \RdKafka\Producer
     */
    public function getProducer()
    {
        return $this->producer;
    }

    /**
     * @param $topicName
     * @return \RdKafka\ProducerTopic
     */
    public function newTopic($topicName)
    {
        return $this->producer->newTopic($topicName);
    }

    /**
     * @param $timeout
     */
    public function poll($timeout)
    {
        return $this->producer->poll($timeout);
    }

    /**
     * @param $timeout
     * @return int
     */
    public function flush($timeout)
    {
        return $this->producer->flush($timeout);
    }

    /**
     * @param $topicName
     * @param array $payload
     * @param int $partition
     * @return bool
     * @throws KafkaException
     */
    public function produce($topicName, array $payload, $partition = 0)
    {
        $uid = $this->getUid($partition);
        $event = new KafkaEvent([
            'topicName' => $topicName,
            'payload' => $payload,
            'partition' => $partition,
            'uid' => $uid,
        ]);
        $this->trigger(self::EVENT_BEFORE_PUSH, $event);
        try {
            $topic = $this->newTopic($topicName);
            $payload = $this->handlePayload($payload, $uid);
            $topic->produce(RD_KAFKA_PARTITION_UA, 0, $payload);
            $this->poll(0);
            $result = $this->flush(10000);

            if (RD_KAFKA_RESP_ERR_NO_ERROR !== $result) {
                throw new \RuntimeException('Was unable to flush, messages might be lost!');
            }
        } catch (\Exception $e) {
            $this->trigger(self::EVENT_AFTER_ERROR, $event);
            throw new KafkaException($e->getMessage());
        }
        return true;
    }

    /**
     * @param $partition
     * @return string
     */
    public function getUid($partition)
    {
        return uniqid($partition);
    }

    /**
     * 处理发送消息
     * @param $payload
     * @param $uid
     * @return string
     */
    public function handlePayload($payload, $uid)
    {
        $payload = $payload + ['uid' => $uid];
        return Json::encode($payload);
    }
}