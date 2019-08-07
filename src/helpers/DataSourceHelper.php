<?php
/**
 * @link https://github.com/Tigrov/yii2-kendoui
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

namespace tigrov\kendoui\helpers;

use yii\db\Schema;

/**
 * Class DataSourceHelper
 *
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */
class DataSourceHelper
{
    const DELTA_YEAR = 500;

    const DEFAULT_KEY_SEPARATOR = '__';

    public static function actions()
    {
        return [
            'create' => ['class' => '\tigrov\kendoui\actions\Create'],
            'read' => ['class' => '\tigrov\kendoui\actions\Read'],
            'update' => ['class' => '\tigrov\kendoui\actions\Update'],
            'destroy' => ['class' => '\tigrov\kendoui\actions\Destroy']
        ];
    }

    /**
     * Static method: Settings for schema object
     *
     * @param \yii\db\ActiveRecord|mixed $model
     * @param array $config ['data' => 'data', 'total' => 'total', 'errors' => 'errors', 'groups' => 'groups', 'aggregates' => 'aggregates',
     *      'attributeNames' => [...], 'exceptAttributes' => [...], 'extraFields' => [...], 'extendMode' => true|false]
     * @param array $args the constructor parameters for model
     * @return array
     */
    public static function schema($model, array $config = [], array $args = [])
    {
        $schema = [];
        foreach (['data', 'total', 'errors', 'groups', 'aggregates'] as $param) {
            $schema[$param] = isset($config[$param]) ? $config[$param] : $param;
            unset($config[$param]);
        }

        if ($model) {
            $schema['model'] = static::model($model, $config, $args);
        }

        return $schema;
    }

    /**
     * Static method: setting for schema model
     *
     * @param \yii\db\ActiveRecord|mixed $model
     * @param array $config ['attributeNames' => [...], 'exceptAttributes' => [...], 'extraFields' => [...], 'extendMode' => true|false, 'keySeparator' => '__']
     * @param array $args the constructor parameters for model
     * @return array
     */
    public static function model($model, array $config = [], array $args = [])
    {
        $model = is_object($model) ? $model : \Yii::createObject($model, $args);
        $model->loadDefaultValues();

        $columns = $model->getTableSchema()->columns;
        $editableAttributes = $model->activeAttributes();

        $attributeNames = isset($config['attributeNames']) ? $config['attributeNames'] : $model->attributes();
        $exceptAttributes = isset($config['exceptAttributes']) ? $config['exceptAttributes'] : [];
        $attributes = array_diff($attributeNames, $exceptAttributes);
        $extraFields = isset($config['extraFields']) ? $config['extraFields'] : [];
        $extendMode = isset($config['extendMode']) || $extraFields;
        $keySeparator = isset($config['keySeparator']) ? $config['keySeparator'] : static::DEFAULT_KEY_SEPARATOR;
        $fields = $extendMode ? $model->fields() : [];

        $result['fields'] = [];
        $keys = $model::primaryKey();
        if (count($keys) === 1) {
            $pk = $keys[0];
            $result['id'] = $pk;
            if (!in_array($pk, $attributes)) {
                $attributes[] = $pk;
            }
        } else {
            $pk = implode($keySeparator, $keys);
            $result['id'] = $pk;
            if (!in_array($pk, $extraFields)) {
                $result['fields'][$pk] = [];
            }
        }

        foreach ($attributes as $attr) {
            $field = $extendMode && isset($fields[$attr]) ? $fields[$attr] : $attr;
            $column = $columns[$attr];

            $result['fields'][$field] = [];
            $type = static::convertType($column->type);
            if ($type !== 'string') {
                $result['fields'][$field]['type'] = $type;
            }
            if (!$column->allowNull && !$column->autoIncrement) {
                $result['fields'][$field]['nullable'] = false;
                $result['fields'][$field]['validation']['required'] = true;
            }
            // Set default value
            if ($model->$field !== null) {
                $result['fields'][$field]['defaultValue'] = $model->$field;
            }
            if ($column->unsigned) {
                $result['fields'][$field]['validation']['min'] = 0;
            }
            if (!in_array($attr, $editableAttributes)) {
                $result['fields'][$field]['editable'] = false;
            }
        }

        foreach ($extraFields as $field) {
            $result['fields'][$field] = [];
            if ($model->$field !== null) {
                $result['fields'][$field]['defaultValue'] = $model->$field;
            }
            if (!$model->canSetProperty($field)) {
                $result['fields'][$field]['editable'] = false;
            }
        }

        return $result;
    }

    /**
     * Convert DB type to Kendo UI type
     *
     * @param string $type DB type
     * @return string Kendo UI type
     */
    public static function convertType($type)
    {
        switch ($type) {
            case Schema::TYPE_BOOLEAN:
                return 'boolean';
            case Schema::TYPE_SMALLINT:
            case Schema::TYPE_INTEGER:
            case Schema::TYPE_BIGINT:
            case Schema::TYPE_FLOAT:
            case Schema::TYPE_DECIMAL:
            case Schema::TYPE_DOUBLE:
            case Schema::TYPE_PK:
            case Schema::TYPE_UPK:
            case Schema::TYPE_BIGPK:
            case Schema::TYPE_UBIGPK:
                return 'number';
            case Schema::TYPE_DATETIME:
            case Schema::TYPE_TIMESTAMP:
            case Schema::TYPE_DATE:
                return 'date';
            case Schema::TYPE_CHAR:
            case Schema::TYPE_STRING:
            case Schema::TYPE_TEXT:
            case Schema::TYPE_BINARY:
            case Schema::TYPE_MONEY:
            case Schema::TYPE_TIME:
            default:
                return 'string';
        }
    }

    /**
     * Converts date to JS format
     * @param mixed $value the date value
     * @param string|null $type the abstract column data type
     * @return string|null
     */
    public static function convertDateToJs($value, $type = null)
    {
        $formatter = \Yii::$app->getFormatter();
        $datetime = static::normalizeDatetimeValue($value, $formatter->defaultTimeZone);
        if ($datetime) {
            $datetime->setTimezone(new \DateTimeZone($formatter->timeZone));
            switch ($type) {
                case Schema::TYPE_DATE:
                    return $datetime->format('Y-m-d');
                case Schema::TYPE_TIME:
                    return $datetime->format('H:i:s');
                // case Schema::TYPE_TIMESTAMP:
                // case Schema::TYPE_DATETIME:
                // case Schema::TYPE_INTEGER:
                // case Schema::TYPE_BIGINT:
                // default:
            }

            return $datetime->format('D M d Y H:i:s \G\M\TO');
        }

        return null;
    }

    /**
     * Converts date to DB format
     * @param mixed $value the date value
     * @param string|null $type the abstract column data type
     * @return string|null
     */
    public static function convertDateToDb($value, $type = null)
    {
        $formatter = \Yii::$app->getFormatter();
        $datetime = static::normalizeDatetimeValue($value, $formatter->timeZone);
        if ($datetime) {
            $datetime->setTimezone(new \DateTimeZone($formatter->defaultTimeZone));
            switch ($type) {
                case Schema::TYPE_DATE:
                    return $datetime->format('Y-m-d');
                case Schema::TYPE_TIME:
                    return $datetime->format('H:i:s');
                case Schema::TYPE_INTEGER:
                case Schema::TYPE_BIGINT:
                    return $datetime->getTimestamp();
                // case Schema::TYPE_TIMESTAMP:
                // case Schema::TYPE_DATETIME:
                // default:
            }

            return $datetime->format('Y-m-d H:i:s');
        }

        return null;
    }

    /**
     * Converts date to \DateTime
     * @param mixed $value the date value
     * @param string|null $timezone
     * @return \DateTime|null
     */
    public static function normalizeDatetimeValue($value, $timezone = null)
    {
        if ($value instanceof \DateTime) {
            return $value;
        } elseif (is_array($value)) {
            // if $value is a \DateTime object which was converted to array.
            return \DateTime::__set_state($value);
        } elseif (is_numeric($value)) { // process as unix timestamp, which is always in UTC
            return new \DateTime('@' . (int)$value, new \DateTimeZone('UTC'));
        } elseif (is_string($value)) {
            return new \DateTime(explode(' (', $value, 2)[0], new \DateTimeZone($timezone));
        }

        return null;
    }

    /**
     * Converts fields value to specified type in action result
     * it shold be run in `Controller::afterAction()`
     * @param array $result action result @see `\tigrov\kendoui\components\Response::getResult()`
     * @param string|string[] $fields fields
     * @param string|callable $type type
     * @return array
     */
    public static function convertResultToType($result, $fields, $type)
    {
        if (!empty($result['data'])) {
            $fields = (array) $fields;
            for ($i = 0, $l = count($result['data']); $i < $l; ++$i) {
                foreach ($fields as $field) {
                    $result['data'][$i][$field] = static::convertValueToType($result['data'][$i][$field], $type);
                }
            }
        }

        return $result;
    }

    /**
     * Converts value to specified type
     * @param mixed $value value
     * @param string|callable $type type
     * @return mixed ($type)
     */
    public static function convertValueToType($value, $type)
    {
        if (is_callable($type)) {
            return call_user_func($type, $value);
        }

        if (is_array($value) && in_array($type, ['string', 'date', 'time', 'datetime', 'timestamp', 'bool', 'boolean', 'int', 'integer', 'number', 'double', 'float'])) {
            foreach ($value as $k => $v) {
                $value[$k] = static::convertValueToType($v, $type);
            }
        }

        switch ($type) {
            case 'string': return (string) $value;
            case 'date':
            case 'time':
            case 'datetime':
            case 'timestamp': return static::convertDateToJs($value, $type);
            case 'bool':
            case 'boolean': return (bool) $value;
            case 'int':
            case 'integer': return (int) $value;
            case 'number':
            case 'double':
            case 'float': return (float) $value;
            case 'array': return (array) $value;
            case 'object': return (object) $value;
        }

        return $value;
    }
}