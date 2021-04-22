<?php

namespace console\controllers;

use common\services\NotifyService;

/**
 * 系统消息消费队列
 * Class NotificationController
 * @package console\controllers
 */
class NotificationController extends BaseController
{
    public $notifyService;

    /**
     * NotificationController constructor.
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

    public function getTopicName()
    {
        return 'notification';
    }

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
        $this->notifyService->sendMessage($payload['notifyId'], $payload['type'], $payload['targets'], $payload['scene']);
    }
}