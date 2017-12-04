<?php
namespace tigrov\kendoui;

use tigrov\kendoui\builders\KendoDataBuilder;
use tigrov\kendoui\helpers\DataSourceHelper;
use yii\base\InvalidConfigException;
use yii\base\Object;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class DataSource
 * @package tigrov\kendoui
 *
 * @property array $actions
 * @property-read array $config
 */

class DataSource extends Object
{
    /** @var array actions for generating transport object */
    public $actions = [];

    /** @var string Controller ID for generating transport urls */
    public $controllerId;

    /** @var array config for DataSource object */
    private $_config = DataSourceHelper::DEFAULT_CONFIG;

    /** @var KendoData */
    private $_kendoData;

    /**
     * @param $config
     */
    public function setConfig($config)
    {
        $this->_config = array_merge($this->_config, $config);
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->_config;
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
            $actions = $this->getTransportActions();
            foreach (['read', 'create', 'update', 'destroy'] as $key) {
                if (isset($actions[$key])) {
                    $this->_kendoData = KendoDataBuilder::build($this->actions[$actions[$key]]['kendoData']);
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
     * Settings for DataSource object
     *
     * @return array
     */
    public function getSettings()
    {
        static $result;
        if ($result === null) {
            $result = ArrayHelper::merge([
                'transport' => $this->getTransport(),
                'schema' => $this->getSchema(),
            ], $this->getConfig());
        }

        return $result;
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
            $transport[$key]['url'] = Url::to([$this->controllerId . '/' . $actionId]);
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
        $createClass = 'tigrov\kendoui\actions\Create';
        $readClass = 'tigrov\kendoui\actions\Read';
        $updateClass = 'tigrov\kendoui\actions\Update';
        $deleteClass = 'tigrov\kendoui\actions\Delete';

        if ($actionClass instanceof $createClass) {
            return 'create';
        } elseif ($actionClass instanceof $readClass) {
            return 'read';
        } elseif ($actionClass instanceof $updateClass) {
            return 'update';
        } elseif ($actionClass instanceof $deleteClass) {
            return 'destroy';
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