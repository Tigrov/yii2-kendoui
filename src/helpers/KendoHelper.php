<?php
namespace tigrov\kendoui\helpers;

class KendoHelper
{
    /**
     * Convert list with keys to Kendo UI values list
     *
     * @param $list [key => value]
     * @return string JSON list of values ["text": value, "value": key]
     */
    public static function toValues($list) {
        $names = array();
        foreach ($list as $k => $v) {
            $names[] = array('text' => $v, 'value' => $k);
        }

        return json_encode($names);
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