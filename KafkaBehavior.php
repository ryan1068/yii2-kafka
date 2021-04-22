<?php
namespace common\components\kafka;

use Yii;
use yii\base\Behavior;
use yii\helpers\VarDumper;

/**
 * Class KafkaBehavior
 * @package common\components\kafka
 */
class KafkaBehavior extends Behavior
{
    /**
     * @var Queue
     */
    public $owner;

    private $_start;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            Queue::EVENT_BEFORE_PUSH => 'beforePush',
            Queue::EVENT_BEFORE_EXEC => 'beforeExec',
            Queue::EVENT_AFTER_EXEC => 'afterExec',
            Queue::EVENT_AFTER_ERROR => 'afterExecError',
        ];
    }

    /**
     * @return \yii\mongodb\Collection
     */
    public function getCollection()
    {
        return Yii::$app->mongodb->getCollection(['cst_ucenter', 'kafka_queue_log']);
    }
    
    /**
     * @param KafkaEvent $event
     * @throws \yii\mongodb\Exception
     */
    public function beforePush(KafkaEvent $event)
    {
        $log = $this->getCollection();
        $log->insert([
            'topic_name' => (string)$event->topicName,
            'group_id' => $event->groupId,
            'partition' => $event->partition,
            'uid' => $event->uid,
            'data' => $event->payload,
            'create_time' => time(),
            'create_date' => date('Y-m-d H:i:s'),
            'start_time' => 0,
            'start_date' => '',
            'end_time' => 0,
            'end_date' => '',
            'spending' => 0,
            'status' => 0,
            'message' => '',
        ]);
    }

    /**
     * @param KafkaEvent $event
     * @throws \yii\mongodb\Exception
     */
    public function beforeExec(KafkaEvent $event)
    {
        $this->_start = microtime(true);
        $log = $this->getCollection();
        $now = time();
        $log->update(['uid' => (string)$event->uid], [
            'start_time' => $now,
            'start_date' => date('Y-m-d H:i:s', $now),
        ]);
    }

    /**
     * @param KafkaEvent $event
     * @throws \yii\mongodb\Exception
     */
    public function afterExec(KafkaEvent $event)
    {
        $log = $this->getCollection();
        $now = time();
        $log->update(['uid' => $event->uid], [
            'status' => 1,
            'end_time' => $now,
            'end_date' => date('Y-m-d H:i:s', $now),
            'spending' => microtime(true) - $this->_start,
            'message' => 'success',
        ]);
    }

    /**
     * @param KafkaEvent $event
     * @throws \yii\mongodb\Exception
     */
    public function afterExecError(KafkaEvent $event)
    {
        $log = $this->getCollection();
        $now = time();
        $log->update(['uid' => $event->uid], [
            'status' => 2,
            'end_time' => $now,
            'end_date' => date('Y-m-d H:i:s', $now),
            'spending' => (microtime(true) - $this->_start) * 1000,
            'message' => VarDumper::export($event->error),
        ]);
    }
}