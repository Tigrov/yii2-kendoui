<?php
/**
 * @link https://github.com/Tigrov/yii2-kendoui
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

namespace tigrov\kendoui;

use yii\base\BaseObject;
use yii\helpers\Json;

/**
 * Class Base
 * @package tigrov\kendoui
 *
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

class Base extends BaseObject
{
    const PARAMS = [];

    /** @var array config for a Kendo object */
    protected $_config = [];

    public function init()
    {
    }

    public function __get($name)
    {
        if (in_array($name, static::PARAMS)) {
            return isset($this->_config[$name])
                ? $this->_config[$name]
                : null;
        }

        return parent::__get($name);
    }

    public function __set($name, $value)
    {
        if (in_array($name, static::PARAMS)) {
            $this->_config[$name] = $value;
        } else {
            parent::__set($name, $value);
        }
    }

    public function __toString()
    {
        return $this->toJSON();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array_filter($this->_config);
    }

    public function toJSON()
    {
        return Json::encode($this->toArray());
    }
}