<?php
namespace tigrov\kendoui;

use tigrov\kendoui\builders\KendoDataBuilder;
use tigrov\kendoui\helpers\DataSourceHelper;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class DataSource
 * @package tigrov\kendoui
 *
 * @property array $actions
 * @property string $controllerId
 */

class DataSource extends BaseDataSource
{
    const DEFAULT_TRANSPORT_CONFIG = [
        'dataType' => 'json',
        'type' => 'POST',
    ];

    /** @var array actions for generating transport object */
    public $actions = [];

    /** @var string Controller ID for generating transport urls */
    public $controllerId;

    /** @var array config for DataSource object */
    private $_config = [
        'batch' => true,
        'serverFiltering' => true,
        'serverSorting' => true,
        'serverPaging' => true,
        'serverAggregates' => true,
        'pageSize' => 20,
    ];

    public function init()
    {
        parent::init();

        if ($this->controllerId === null) {
            $this->controllerId = \Yii::$app->controller->getUniqueId();
        }

        $this->_config = ArrayHelper::merge(
            ['transport' => $this->getTransport()],
            $this->_config
        );
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
            $transport[$key] = static::DEFAULT_TRANSPORT_CONFIG;
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
}