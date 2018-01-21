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
 * @property array $transportConfig
 */

class DataSource extends BaseDataSource
{
    /** @var array actions for generating transport object */
    public $actions = [];

    /** @var string Controller ID for generating transport urls */
    public $controllerId;

    /** @var array config for DataSource object */
    protected $_config = [
        'batch' => true,
        'serverFiltering' => true,
        'serverSorting' => true,
        'serverPaging' => true,
        'serverAggregates' => true,
        'pageSize' => 20,
    ];

    protected $_transportConfig = [
        'dataType' => 'json',
        'type' => 'POST',
    ];

    private $_transportActions;

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

    public function setTransportConfig($config)
    {
        $this->_transportConfig = array_merge($this->_transportConfig, $config);
    }

    public function getTransportConfig()
    {
        return $this->_transportConfig;
    }

    /**
     * @param array|string $config
     * @return KendoData
     * @throws InvalidConfigException
     */
    public function getKendoData($config = [])
    {
        if ($this->_kendoData === null) {
            if ($config && is_string($config)) {
                $config = ['model' => ['class' => $config]];
            }
            $transportActions = $this->getTransportActions();
            foreach (['read', 'create', 'update', 'destroy'] as $key) {
                if (isset($transportActions[$key])) {
                    $this->_kendoData = KendoDataBuilder::build(ArrayHelper::merge($this->actions[$transportActions[$key]]['kendoData'], $config));
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
     * @inheritdoc
     */
    public function setKendoData($config)
    {
        $className = KendoDataBuilder::CLASS_NAME;
        $this->_kendoData = $config instanceof $className
            ? $config
            : $this->getKendoData($config);
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
     * Settings for transport object
     *
     * @return array
     */
    public function getTransport()
    {
        $transport = [];
        foreach ($this->getTransportActions() as $key => $actionId) {
            $transport[$key] = $this->getTransportConfig();
            $transport[$key]['url'] = Url::to(['/' . $this->controllerId . '/' . $actionId]);
        }

        return $transport;
    }

    private function getTransportActions()
    {
        if ($this->_transportActions === null) {
            $this->_transportActions = [];
            foreach ($this->actions as $actionId => $settings) {
                if ($key = $this->getTransportKey($settings['class'])) {
                    $this->_transportActions[$key] = $actionId;
                }
            }
        }

        return $this->_transportActions;
    }

    private function getTransportKey($actionClass)
    {
        foreach (DataSourceHelper::actions() as $key => $action) {
            if (is_a($actionClass, $action['class'], true)) {
                return $key;
            }
        }

        return null;
    }
}