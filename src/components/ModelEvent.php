<?php
/**
 * @link https://github.com/Tigrov/yii2-kendoui
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

namespace tigrov\kendoui\components;

/**
 * ModelEvent represents the parameter needed by [[Model]] events.
 *
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */
class ModelEvent extends BaseEvent
{
    /**
     * @var bool whether the model is in valid status. Defaults to true.
     * A model is in valid status if it passes validations or certain checks.
     */
    public $isValid = true;
}