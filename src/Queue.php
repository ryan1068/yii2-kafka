<?php


namespace yii\kafka;


use yii\base\Component;
use yii\helpers\ArrayHelper;

abstract class Queue extends Component
{
    /**
     * @event PushEvent
     */
    const EVENT_BEFORE_PUSH = 'beforePush';
    /**
     * @event ExecEvent
     */
    const EVENT_BEFORE_EXEC = 'beforeExec';
    /**
     * @event ExecEvent
     */
    const EVENT_AFTER_EXEC = 'afterExec';
    /**
     * @event PushEvent
     */
    const EVENT_AFTER_ERROR = 'afterExecError';

    /**
     * @param string $name
     * @return mixed
     */
    public function getConfig($name = '')
    {
        return ArrayHelper::getValue(\Yii::$app->params['kafka'], $name, []);
    }
}