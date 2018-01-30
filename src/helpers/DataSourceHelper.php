<?php
namespace tigrov\kendoui\helpers;

use yii\db\Schema;

class DataSourceHelper
{
    const DELTA_YEAR = 500;

    const DEFAULT_KEY_SEPARATOR = '__';

    const PARAMS = [
        'aggregate', 'autoSync', 'batch', 'data', 'filter', 'group', 'inPlaceSort', 'offlineStorage',
        'page', 'pageSize', 'serverAggregates', 'serverFiltering', 'serverGrouping', 'serverPaging',
        'serverSorting', 'sort', 'type', 'transport', 'schema'
    ];

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
            if (($type = static::convertType($column->type)) !== 'string') {
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
     * Prepare date or time or datetime for response
     * @param mixed $value the date value
     * @return null|string
     */
    public static function prepareDate($value)
    {
        if ($value) {
            if (is_int($value)) {
                $value = new \DateTime('@' . $value);
            } elseif (is_string($value)) {
                $value = new \DateTime($value);
            } elseif (is_array($value)) {
                $value = \DateTime::__set_state($value);
            }
            if ($value instanceof \DateTime) {
                if ($value->getTimestamp()) {
                    return $value->format(\DateTime::ATOM);
                }
            }
        }

        return null;
    }

    /**
     * Parse date from request
     * @param string $value the date value
     * @return null|string
     */
    public static function parseDate($value)
    {
        return self::_composeDate(date_parse($value));
    }

    /**
     * Parse time from request
     * @param string $value the time value
     * @return null|string
     */
    public static function parseTime($value)
    {
        return self::_composeTime(date_parse($value));
    }

    /**
     * Parse datetime from request
     * @param string $value the datetime value
     * @return null|string
     */
    public static function parseDateTime($value)
    {
        $parsed = date_parse($value);
        if ($date = self::_composeDate($parsed)) {
            if ($time = self::_composeTime($parsed)) {
                return $date . ' ' . $time;
            }

            return $date . ' 00:00:00';
        }

        return null;
    }

    private static function _composeDate($data)
    {
        return checkdate($data['month'], $data['day'], $data['year'])
            ? $data['year']
                . '-' . str_pad($data['month'], 2, '0', STR_PAD_LEFT)
                . '-' . str_pad($data['day'], 2, '0', STR_PAD_LEFT)
            : null;
    }

    private static function _composeTime($data)
    {
        return $data['hour'] || $data['minute'] || $data['second'] || $data['fraction']
            ? str_pad($data['hour'], 2, '0', STR_PAD_LEFT)
            . ':' . str_pad($data['minute'], 2, '0', STR_PAD_LEFT)
            . ':' . str_pad($data['second'], 2, '0', STR_PAD_LEFT)
            . '.' . rtrim((int)($data['fraction'] * 1000000), '0')
            : null;
    }
}