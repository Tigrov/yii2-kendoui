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
                $this->_actions = array_filter(function ($v) {
                    return StringHelper::startsWith($v, $this->actionsPrefix);
                }, $this->_actions);
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
     * Settings for model object
     *
     * @return array
     */
    public function getModel()
    {
        $actionInstance = $this->getActionInstance();
        $modelInstance = $actionInstance->getModelInstance();
        $columns = $modelInstance->getTableSchema()->columns;
        $editableAttributes = $modelInstance->activeAttributes();

        $attributes = $actionInstance->getAttributes(true);
        $extraFields = $actionInstance->getExtraFields();
        $extendMode = $actionInstance->getExtendMode();
        $fields = $extendMode ? $modelInstance->fields() : [];

        $model = [];
        $keys = $modelInstance::primaryKey();
        if (count($keys) === 1) {
            $pk = $keys[0];
            $model['id'] = $pk;
            if (!in_array($pk, $attributes)) {
                $attributes[] = $pk;
            }
        } else {
            $pk = implode('__', $keys);
            $model['id'] = $pk;
            if (!in_array($pk, $extraFields)) {
                $extraFields[] = $pk;
            }
        }

        $model['fields'] = [];
        foreach ($attributes as $attr) {
            $field = $extendMode && isset($fields[$attr]) ? $fields[$attr] : $attr;
            $column = $columns[$attr];

            $model['fields'][$field] = ['type' => static::convertType($column->type)];
            if (!$column->allowNull && !$column->autoIncrement) {
                $model['fields'][$field]['nullable'] = false;
                $model['fields'][$field]['validation']['required'] = true;
            }
            if ($column->defaultValue) {
                $model['fields'][$field]['defaultValue'] = $column->defaultValue;
            }
            if ($column->unsigned) {
                $model['fields'][$field]['validation']['min'] = 0;
            }
            if (!in_array($attr, $editableAttributes)) {
                $model['fields'][$field]['editable'] = false;
            }
        }

        foreach ($extraFields as $field) {
            $model['fields'][$field] = [];
        }

        return $model;
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