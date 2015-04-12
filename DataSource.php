<?php
namespace tigrov\kendoui;

use yii\base\Object;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\helpers\StringHelper;
use yii\web\Controller;
use tigrov\kendoui\actions\Action;
use tigrov\kendoui\actions\Create;
use tigrov\kendoui\actions\Read;
use tigrov\kendoui\actions\Update;
use tigrov\kendoui\actions\Delete;

/**
 * Class DataSource
 * @package tigrov\kendoui
 *
 * @property-read Action $actionInstance instance of Action for generate model
 */

class DataSource extends Object
{
    /**
     * @var array action IDs for generate transport object
     */
    public $actionIds = [];

    /**
     * @var string prefix for filtering actions
     */
    public $actionsPrefix;

    /**
     * @var string ID for $actionInstance
     */
    public $actionInstanceId;

    /**
     * @var array config for $actionInstance
     */
    public $actionConfig = [];

    private $_config = [
        'batch' => true,
        'serverFiltering' => true,
        'serverSorting' => true,
        'serverPaging' => true,
        'serverAggregates' => true,
    ];
    private $_controller;
    private $_actions;
    private $_actionInstance;
    private $_labels;
    private $_result;

    /**
     * @param $config
     * @return array list of $config
     */
    public function setConfig($config)
    {
        return $this->_config = array_merge($this->_config, $config);
    }

    /**
     * @return array|string list of $config
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * @param Controller $controller
     * @throws \Yii\base\UnknownClassException
     */
    public function setController(Controller $controller)
    {
        $this->_controller = $controller;
    }

    /**
     * @return Controller
     */
    public function getController()
    {
        if ($this->_controller === null) {
            $this->_controller = \Yii::$app->controller;
        }

        return $this->_controller;
    }

    /**
     * @return array
     */
    public function getActions()
    {
        if ($this->_actions === null) {
            $this->_actions = $this->getController()->actions();
            if ($this->actionIds) {
                $this->_actions = array_intersect_key($this->_actions, array_flip($this->actionIds));
            }
            if ($this->actionsPrefix) {
                $this->_actions = array_filter(
                    $this->_actions,
                    function ($k) {
                        return StringHelper::startsWith($k, $this->actionsPrefix);
                    },
                    ARRAY_FILTER_USE_KEY
                );
            }
        }

        return $this->_actions;
    }

    /**
     * @return Action
     */
    public function getActionInstance()
    {
        if ($this->_actionInstance === null) {
            $actions = $this->getActions();
            $id = $this->actionInstanceId ?: array_keys($actions)[0];
            $action = ArrayHelper::merge(['id' => $id], $actions[$id], $this->actionConfig);

            $this->_actionInstance = \Yii::createObject($action, [$id, $this->getController()]);
        }

        return $this->_actionInstance;
    }

    /**
     * Settings for DataSource object
     *
     * @return array
     */
    public function getSettings()
    {
        if ($this->_result === null) {
            $this->_result = [
                'transport' => $this->getTransport(),
                'schema' => $this->getSchema(),
            ];

            foreach ($this->config as $k => $v) {
                $this->_result[$k] = $v;
            }
        }

        return $this->_result;
    }

    /**
     * Settings for transport object
     *
     * @return array
     */
    public function getTransport()
    {
        $controller = $this->getController();
        $actions = $this->getActions();

        $createClass = Create::className();
        $readClass = Read::className();
        $updateClass = Update::className();
        $deleteClass = Delete::className();

        $common = [
            'dataType' => 'json',
            'type' => 'POST',
        ];

        $transport = [];
        foreach ($actions as $actionId => $settings) {
            $key = null;
            if (is_a($settings['class'], $createClass, true)) {
                $key = 'create';
            } elseif (is_a($settings['class'], $readClass, true)) {
                $key = 'read';
            } elseif (is_a($settings['class'], $updateClass, true)) {
                $key = 'update';
            } elseif (is_a($settings['class'], $deleteClass, true)) {
                $key = 'destroy';
            }

            if ($key) {
                $transport[$key] = $common;
                $transport[$key]['url'] = Url::to([$controller->id . '/' . $actionId]);
            }
        }

        return $transport;
    }

    /**
     * Settings for schema object
     *
     * @return array
     */
    public function getSchema()
    {
        $actionInstance = $this->getActionInstance();
        return [
            'data' => $actionInstance->getResponseParams('data'),
            'total' => $actionInstance->getResponseParams('total'),
            'errors' => $actionInstance->getResponseParams('errors'),
            'groups' => $actionInstance->getResponseParams('groups'),
            'aggregates' => $actionInstance->getResponseParams('aggregates'),
            'model' => $this->getModel(),
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
     * Settings for schema model
     *
     * @return array
     */
    public function getModel()
    {
        $actionInstance = $this->getActionInstance();
        $modelInstance = $actionInstance->getModelInstance();

        return static::model($modelInstance, [
            'attributeNames' => $actionInstance->getAttributes(true),
            'extraFields' => $actionInstance->getExtraFields(),
            'extendMode' => $actionInstance->getExtendMode(),
            'keySeparator' => $actionInstance->keySeparator,
        ]);
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
        $keySeparator = isset($config['keySeparator']) ? $config['keySeparator'] : Action::DEFAULT_KEY_SEPARATOR;
        $fields = $extendMode ? $model->fields() : [];

        $result = [];
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
                $extraFields[] = $pk;
            }
        }

        $result['fields'] = [];
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
     * Labels for forms and other
     *
     * @return array label list
     */
    public function getLabels()
    {
        if ($this->_labels === null) {
            $actionInstance = $this->getActionInstance();
            $modelInstance = $actionInstance->getModelInstance();
            $labels = $modelInstance->attributeLabels();
            if (!$actionInstance->getExtendMode()) {
                return $labels;
            }

            $attributes = $actionInstance->getAttributes(true);
            $extraFields = $actionInstance->getExtraFields();
            $fields = $modelInstance->fields();
            $fields = array_merge(
                array_combine($attributes, $attributes),
                $fields,
                array_combine($extraFields, $extraFields));

            $this->_labels = [];
            foreach ($fields as $name => $field) {
                if (isset($labels[$name])) {
                    $this->_labels[$field] = $labels[$name];
                }
            }
        }

        return $this->_labels;
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