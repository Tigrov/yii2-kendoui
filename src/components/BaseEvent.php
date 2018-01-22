<?php
namespace tigrov\kendoui\components;

use yii\base\Event;
use yii\db\ActiveRecord;

class BaseEvent extends Event
{
    /** @var ActiveRecord|array The model or values of model's attributes of the event */
    public $model;
}