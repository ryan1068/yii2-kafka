# yii2-kafka
### 通过docker安装kafka，zookeeper服务
#### docker-compose配置：
```php
### ZooKeeper #########################################
    zookeeper:
      build: ./zookeeper
      volumes:
        - ${DATA_PATH_HOST}/zookeeper/data:/data
        - ${DATA_PATH_HOST}/zookeeper/datalog:/datalog
      ports:
        - "${ZOOKEEPER_PORT}:2181"
      networks:
        - backend

### kafka ####################################################
    kafka:
      image: wurstmeister/kafka
      ports:
        - "9092:9092"
      environment:
        KAFKA_BROKER_ID: 1
        KAFKA_ADVERTISED_HOST_NAME: 192.168.0.1
        KAFKA_ADVERTISED_LISTENERS: PLAINTEXT://192.168.0.1:9092
        KAFKA_MESSAGE_MAX_BYTES: 2000000
        KAFKA_ZOOKEEPER_CONNECT: zookeeper:2181
      volumes:
        - ./kafka:/kafka
        - /var/run/docker.sock:/var/run/docker.sock
      networks:
        - backend
        
### kafka-manager ####################################################
    kafka-manager:
      image: sheepkiller/kafka-manager
      ports:
        - 9020:9000
      environment:
        ZK_HOSTS: zookeeper:2181
      networks:
        - backend
```

#### Yii2配置：

Config:
```php
'components' => [
    'kafka' => [
        'class' => yii\kafka\Producer::class,
        'as log' => yii\kafka\KafkaBehavior::class,
    ],
]
```

Params:
```php
'params' => [
    'kafka' => [
        'broker_list' => '192.168.0.1:9092,192.168.0.2:9092,192.168.0.3:9092',
        'topic' => [
            'auto.commit.interval.ms' => 100,
            'offset.store.method' => 'broker',
            'auto.offset.reset' => 'earliest',
        ],
    ],
]
```

#### 使用示例:
```php
// 消费者使用示例
<?php
namespace console\controllers;

use common\services\NotifyService;

/**
 * 系统消息消费队列
 * Class DemoController
 * @package console\controllers
 */
class DemoController extends ConsumerController
{
    public $notifyService;

    /**
     * DemoController constructor.
     * @param $id
     * @param $module
     * @param NotifyService $notifyService
     * @param array $config
     */
    public function __construct($id, $module, NotifyService $notifyService, $config = [])
    {
        $this->notifyService = $notifyService;
        parent::__construct($id, $module, $config);
    }

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();
        $this->consumer->attachBehavior('kafka', [
            'class' => KafkaBehavior::class,
            'tableName' => 'kafka_queue_log'
        ]);
    }

    /**
     * @return string 主题名称
     */
    public function getTopicName()
    {
        return 'notification';
    }

    /**
     * @return string 分组id
     */
    public function getGroupId()
    {
        return 'notificationGroup';
    }

    /**
     * @param $payload
     * @throws \yii\base\UserException
     */
    public function consume($payload)
    {
        $this->notifyService->sendMessage($payload['id'], $payload['scene']);
    }
}

// 生产者使用示例
\Yii::$app->kafka->produce('notification', ['id' => 1, 'scene' => 0]);

```
