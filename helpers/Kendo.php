<?php
namespace tigrov\kendoui\helpers;


class Kendo {

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

    public static function escape($value, $count = 1)
    {
        if (is_array($value)) {
            return array_map([__CLASS__, 'escapeStr'], $value, array_fill(0, count($value) - 1, $count));
        }

        return static::escapeStr($value, $count);
    }

    public static function escapeStr($value, $count = 1)
    {
        return str_replace('#', str_repeat('\\', $count) . '#', $value);
    }
} 