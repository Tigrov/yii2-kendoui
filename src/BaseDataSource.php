<?php
namespace tigrov\kendoui;

use tigrov\kendoui\builders\KendoDataBuilder;
use tigrov\kendoui\helpers\DataSourceHelper;
use yii\base\BaseObject;
use yii\helpers\ArrayHelper;

/**
 * Class DataSource
 * @package tigrov\kendoui
 *
 * @property KendoData $kendoData
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