<?php
namespace yii\kafka;

use yii\helpers\Json;

/**
 * Class Consumer
 * @package common\components\kafka
 */
class Consumer extends Queue
{
    /**
     * @var string
     */
    public $groupId;
    /**
     * @var string
     */
    public $topicName;
    /**
     * @var \RdKafka\Consumer
     */
    protected $consumer;
    /**
     * @var \RdKafka\ConsumerTopic
     */
    protected $topic;

    /**
     * Consumer constructor.
     * @param $groupId
     * @param $topicName
     * @param array $config
     * @throws KafkaException
     */
    public function __construct($groupId, $topicName, $config = [])
    {
        $this->setGroupId($groupId);
        $this->setTopicName($topicName);
        $this->setConsumer();
        parent::__construct($config);
    }

    /**
     * @throws KafkaException
     */
    public function setConsumer()
    {
        $conf = new \RdKafka\Conf();

        // Set the group id. This is required when storing offsets on the broker
        $conf->set('group.id', $this->getGroupId());

        $this->consumer = new \RdKafka\Consumer($conf);
        $this->consumer->addBrokers($this->getConfig('broker_list'));
    }

    /**
     * @return \RdKafka\Consumer
     */
    public function getConsumer()
    {
        return $this->consumer;
    }

    /**
     * @param $groupId
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;
    }

    /**
     * @return string
     * @throws KafkaException
     */
    public function getGroupId()
    {
        if (empty($this->groupId)) {
            throw new KafkaException('必须设置group_id');
        }
        return $this->groupId;
    }

    /**
     * @param $topicName
     */
    public function setTopicName($topicName)
    {
        $this->topicName = $topicName;
    }

    /**
     * @return string
     * @throws KafkaException
     */
    public function getTopicName()
    {
        if (empty($this->topicName)) {
            throw new KafkaException('必须设置topic_name');
        }
        return $this->topicName;
    }

    /**
     * @param $topicName
     * @return \RdKafka\ConsumerTopic
     * @throws KafkaException
     */
    public function newTopic()
    {
        $topicConf = new \RdKafka\TopicConf();
        foreach ($this->getConfig('topic') as $key => $value) {
            $topicConf->set($key, $value);
        }

        return $this->consumer->newTopic($this->getTopicName(), $topicConf);
    }

    /**
     * @param callable $callable
     * @param $payload
     */
    public function consumer(callable $callable, $payload)
    {
        $payload = Json::decode($payload);
        $event = new KafkaEvent([
            'uid' => $payload['uid'] ?? 0,
        ]);
        $this->trigger(self::EVENT_BEFORE_EXEC, $event);
        try {
            $callable($payload);
            $this->trigger(self::EVENT_AFTER_EXEC, $event);
        } catch (\Exception $e) {
            $this->trigger(self::EVENT_AFTER_ERROR, $event);
            throw new KafkaException($e->getMessage());
        }
    }
}