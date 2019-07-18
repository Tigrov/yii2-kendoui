<?php
/**
 * @link https://github.com/Tigrov/yii2-kendoui
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

namespace tigrov\kendoui;

use tigrov\kendoui\builders\KendoDataBuilder;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;

/**
 * Class DataSourceData
 *
 * @property \yii\db\ActiveRecord[] $models
 *
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

class DataSourceData extends BaseDataSource
{
    /** @var \yii\db\ActiveRecord[]|array|null list of models or models' rows to be converted in DataSource.data */
    public $models;

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
        if (is_array($config) && empty($config['model']) && $this->models) {
            $model = reset($this->models);
            if ($model instanceof \yii\db\ActiveRecord) {
                $config['model'] = get_class($model);
            }
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
        $data = [];
        $kendoData = $this->getKendoData();
        if ($this->models && reset($this->models) instanceof \yii\db\ActiveRecord) {
            foreach ($this->models as $model) {
                $data[] = $kendoData->getModelData($model);
            }
        } else {
            $rows = $this->models && is_array(reset($this->models))
                ? $this->models
                : $kendoData->getActiveQuery()->asArray()->all();

            $data = $kendoData->getExtendMode()
                ? $kendoData->toModelArray($rows)
                : $kendoData->filterAttributes($rows);
        }

        return $data;
    }
}