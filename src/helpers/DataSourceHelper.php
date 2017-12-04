<?php
namespace tigrov\kendoui\helpers;

use yii\base\Object;

class DataSourceHelper extends Object
{
    const DEFAULT_KEY_SEPARATOR = '__';

    const DEFAULT_CONFIG = [
        'batch' => true,
        'serverFiltering' => true,
        'serverSorting' => true,
        'serverPaging' => true,
        'serverAggregates' => true,
        'pageSize' => 20,
    ];

    const DEFAULT_TRANSPORT_CONFIG = [
        'dataType' => 'json',
        'type' => 'POST',
    ];

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
            case 'boolean':
                return 'boolean';
            case 'smallint':
            case 'integer':
            case 'bigint':
            case 'float':
            case 'decimal':
                return 'number';
            case 'datetime':
            case 'timestamp':
            case 'date':
                return 'date';
            case 'string':
            case 'text':
            case 'binary':
            case 'money':
            case 'time':
            default:
                return 'string';
        }
    }
}