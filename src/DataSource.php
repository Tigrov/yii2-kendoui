<?php
namespace tigrov\kendoui;

use tigrov\kendoui\builders\KendoDataBuilder;
use tigrov\kendoui\helpers\DataSourceHelper;
use yii\base\InvalidConfigException;
use yii\base\BaseObject;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class DataSource
 * @package tigrov\kendoui
 *
 * @property array $actions
 * @property string $controllerId
 * @property KendoData $kendoData
 */

class DataSource extends BaseObject
{
    /** @var array actions for generating transport object */
    public $actions = [];

    /** @var string Controller ID for generating transport urls */
    public $controllerId;

    /** @var KendoData */
    private $_kendoData;

    /** @var array config for DataSource object */
    private $_config = DataSourceHelper::DEFAULT_CONFIG;

    public function init()
    {
        if ($this->controllerId === null) {
            $this->controllerId = \Yii::$app->controller->getUniqueId();
        }

        $this->_config = ArrayHelper::merge(
            ['transport' => $this->getTransport(), 'schema' => $this->getSchema()],
            $this->_config
        );
    }

    public function __get($name)
    {
        if (in_array($name, DataSourceHelper::PARAMS)) {
            return isset($this->_config[$name])
                ? $this->_config[$name]
                : null;
        }

        return parent::__get($name);
    }

    public function __set($name, $value)
    {
        if (in_array($name, DataSourceHelper::PARAMS)) {
            $this->_config[$name] = $value;
        }

        parent::__set($name, $value);
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
        return json_encode($this->toArray());
    }

    public function setKendoData($config)
    {
        $this->_kendoData = KendoDataBuilder::build($config);
    }

    /**
     * @return KendoData
     * @throws InvalidConfigException
     */
    public function getKendoData()
    {
        if ($this->_kendoData === null) {
            $transportActions = $this->getTransportActions();
            foreach (['read', 'create', 'update', 'destroy'] as $key) {
                if (isset($transportActions[$key])) {
                    $this->_kendoData = KendoDataBuilder::build($this->actions[$transportActions[$key]]['kendoData']);
                    break;
                }
            }

            if ($this->_kendoData === null) {
                throw new InvalidConfigException('DataSource configuration must be an array containing "kendoData" or "actions" with at least one action from the namespace tigrov\kendoui\actions (Read, Create, Update, Delete).');
            }
        }

        return $this->_kendoData;
    }

    /**
     * Settings for transport object
     *
     * @return array
     */
    public function getTransport()
    {
        $transport = [];
        foreach ($this->getTransportActions() as $key => $actionId) {
            $transport[$key] = DataSourceHelper::DEFAULT_TRANSPORT_CONFIG;
            $transport[$key]['url'] = Url::to(['/' . $this->controllerId . '/' . $actionId]);
        }

        return $transport;
    }

    public function getTransportActions()
    {
        static $result;
        if ($result === null) {
            $result = [];
            foreach ($this->actions as $actionId => $settings) {
                if ($key = static::transportKey($settings['class'])) {
                    $result[$key] = $actionId;
                }
            }
        }

        return $result;
    }

    public static function transportKey($actionClass)
    {
        foreach (DataSourceHelper::actions() as $key => $action) {
            if (is_a($actionClass, $action['class'], true)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Settings for schema object
     * @return array
     */
    public function getSchema()
    {
        $kendoData = $this->getKendoData();
        $response = $kendoData->getResponse();
        return $response->getParams() + ['model' => $this->getModel()];
    }

    /**
     * Settings for schema model
     * @return array
     */
    public function getModel()
    {
        $kendoData = $this->getKendoData();
        $modelInstance = $kendoData->getModelInstance();

        return DataSourceHelper::model($modelInstance, [
            'attributeNames' => $kendoData->getAttributes(true),
            'extraFields' => $kendoData->extraFields,
            'extendMode' => $kendoData->getExtendMode(),
            'keySeparator' => $kendoData->keySeparator,
        ]);
    }
}