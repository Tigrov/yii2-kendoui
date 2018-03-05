<?php
/**
 * @link https://github.com/Tigrov/yii2-kendoui
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

namespace tigrov\kendoui\components;

use yii\base\Event;
use yii\db\ActiveRecord;

/**
 * Class BaseEvent
 * 
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */
class BaseEvent extends Event
{
    /** @var ActiveRecord|array The model or values of model's attributes of the event */
    public $model;
}