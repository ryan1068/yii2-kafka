<?php

namespace yii\kafka\console\controllers;

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
     * 开启新消费者需重新定义topicName
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
     * 消费者实际执行业务代码的方法
     * @param $payload
     * @throws \yii\base\UserException
     */
    public function consume($payload)
    {
        $this->notifyService->sendMessage($payload['id'], $payload['scene']);
    }
}