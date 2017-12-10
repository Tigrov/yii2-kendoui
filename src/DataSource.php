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
    public $pageSize = 20;
    public $batch = true;
    public $serverFiltering = true;
    public $serverSorting = true;
    public $serverPaging = true;
    public $serverAggregates = true;
    public $serverGrouping = false;
    public $aggregate;
    public $autoSync = false;
    public $data;
    public $filter;
    public $group;
    public $inPlaceSort = false;
    public $offlineStorage;
    public $page;
    public $sort;
    public $type = 'odata';

    public $transport = [];
    public $schema = [];

    /** @var array actions for generating transport object */
    private $_actions = [];

    /** @var string Controller ID for generating transport urls */
    private $_controllerId;

    /** @var KendoData */
    private $_kendoData;

    public function init()
    {
        if ($this->getControllerId() === null) {
            $this->setControllerId(\Yii::$app->controller->getUniqueId());
        }

        $this->transport = ArrayHelper::merge($this->getTransport(), $this->transport);
        $this->schema = ArrayHelper::merge($this->getSchema(), $this->schema);
    }

    public function setActions($value)
    {
        $this->_actions = $value;
    }

    public function getActions()
    {
        return $this->_actions;
    }

    public function setControllerId($value)
    {
        $this->_controllerId = $value;
    }

    public function getControllerId()
    {
        return $this->_controllerId;
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
            $actions = $this->getActions();
            $transportActions = $this->getTransportActions();
            foreach (['read', 'create', 'update', 'destroy'] as $key) {
                if (isset($transportActions[$key])) {
                    $this->_kendoData = KendoDataBuilder::build($actions[$transportActions[$key]]['kendoData']);
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
            $transport[$key]['url'] = Url::to([$this->getControllerId() . '/' . $actionId]);
        }

        return $transport;
    }

    public function getTransportActions()
    {
        static $result;
        if ($result === null) {
            $result = [];
            foreach ($this->getActions() as $actionId => $settings) {
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