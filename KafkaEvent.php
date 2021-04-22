<?php


namespace common\components\kafka;


use yii\base\Event;

/**
 * Class KafkaEvent
 * @package common\components\kafka
 */
class KafkaEvent extends Event
{
    /**
     * @var string 分组id
     */
    public $groupId;
    /**
     * @var string topic名称
     */
    public $topicName;
    /**
     * @var array 生产者push的数据
     */
    public $payload;
    /**
     * @var int 磁盘分区
     */
    public $partition;
    /**
     * @var int 数据偏移量
     */
    public $offset;
    /**
     * @var int 消息唯一标识
     */
    public $uid;
    /**
     * @var string 报错消息
     */
    public $error;
}