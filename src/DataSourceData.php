<?php
namespace tigrov\kendoui;

use tigrov\kendoui\builders\KendoDataBuilder;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\db\ActiveRecord;

/**
 * Class DataSource
 * @package tigrov\kendoui
 *
 * @property ActiveRecord[] $models
 */

class DataSourceData extends BaseDataSource
{
    /** @var ActiveRecord[] */
    public $models = [];

    public function init()
    {
        parent::init();

        $this->_config = ArrayHelper::merge(
            ['data' => $this->getData()],
            $this->_config
        );
    }

    public function setKendoData($config = [])
    {
        if (!is_string($config) && empty($config['model'])) {
            $model = reset($this->models);
            $config['model'] = $model::className();
        }
        $this->_kendoData = KendoDataBuilder::build($config);
    }

    /**
     * @return KendoData
     * @throws InvalidConfigException
     */
    public function getKendoData()
    {
        if ($this->_kendoData === null) {
            $this->setKendoData();
        }

        return $this->_kendoData;
    }

    public function getData()
    {
        $list = [];
        $kendoData = $this->getKendoData();
        foreach ($this->models as $model) {
            $list[] = $kendoData->getModelData($model);
        }

        return $list;
    }
}