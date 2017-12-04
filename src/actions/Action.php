<?php
namespace tigrov\kendoui\actions;

use tigrov\kendoui\builders\KendoDataBuilder;
use tigrov\kendoui\KendoData;

abstract class Action extends \yii\base\Action
{
    /** @var KendoData */
    private $_kendoData;

    /**
     * @inheritdoc
     */
    public function run()
    {
        \Yii::$app->getResponse()->format = $this->getKendoData()->getResponse()->format;

        $this->process();

        return $this->getResult();
    }

    public function setKendoData($config)
    {
        $this->_kendoData = KendoDataBuilder::build($config);
    }

    public function getKendoData()
    {
        return $this->_kendoData;
    }

    public function getResult()
    {
        return $this->getKendoData()->getResponse()->getResult();
    }

    abstract public function process();
}