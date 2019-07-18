<?php
/**
 * @link https://github.com/Tigrov/yii2-kendoui
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

namespace tigrov\kendoui\helpers;

use yii\helpers\Json;

/**
 * Class KendoHelper
 *
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */
class KendoHelper
{
    /**
     * Convert list with keys to Kendo UI values list
     *
     * @param $list [key => value]
     * @return string JSON list of values ["text": value, "value": key]
     */
    public static function toValues($list) {
        $names = [];
        foreach ($list as $k => $v) {
            $names[] = ['text' => $v, 'value' => $k];
        }

        return Json::encode($names);
    }

    public static function escape($value)
    {
        if (is_array($value)) {
            return array_map([__CLASS__, 'escapeStr'], $value);
        }

        return static::escapeStr($value);
    }

    public static function escapeStr($value)
    {
        return str_replace("'", '\\u0027', $value);
    }
}