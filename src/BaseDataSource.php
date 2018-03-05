<?php
/**
 * @link https://github.com/Tigrov/yii2-kendoui
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

namespace tigrov\kendoui;

use tigrov\kendoui\builders\KendoDataBuilder;
use tigrov\kendoui\helpers\DataSourceHelper;
use yii\base\BaseObject;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * Class DataSource
 * @package tigrov\kendoui
 *
 * @property KendoData $kendoData
 * @property-read array $model
 *
 * Kendo UI DataSource properties
 * @property array $aggregate
 * @property bool $autoSync
 * @property bool $batch
 * @property array|string $data
 * @property array $filter
 * @property array $group
 * @property bool $inPlaceSort
 * @property array|string $offlineStorage
 * @property int $page
 * @property int $pageSize
 * @property bool $serverAggregates
 * @property bool $serverFiltering
 * @property bool $serverGrouping
 * @property bool $serverPaging
 * @property bool $serverSorting
 * @property array $sort
 * @property string $type
 * @property array $transport
 * @property array $schema
 *
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

class BaseDataSource extends BaseObject
{
    /** @var KendoData */
    protected $_kendoData;

    /** @var array config for DataSource object */
    protected $_config = [
        'pageSize' => 20,
    ];

    public function init()
    {
        parent::init();

        $this->_config = ArrayHelper::merge(
            ['schema' => $this->getSchema()],
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
        } else {
            parent::__set($name, $value);
        }
    }

    public function __toString()
    {
        return $this->toJSON();
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
        return Json::encode($this->toArray());
    }

    public function setKendoData($config)
    {
        $this->_kendoData = KendoDataBuilder::build($config);
    }

    /**
     * @return KendoData
     */
    public function getKendoData()
    {
        return $this->_kendoData;
    }

    /**
     * Settings for schema object
     * @return array
     */
    public function getSchema()
    {
        return ['model' => $this->getModel()];
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