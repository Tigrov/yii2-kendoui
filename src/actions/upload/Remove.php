<?php

namespace tigrov\kendoui\actions\upload;

use yii\base\Action;
use yii\helpers\FileHelper;

class Remove extends Action
{
    public $removeField = 'fileNames';

    public $uploadPath;

    /**
     * @inheritdoc
     */
    public function run()
    {
        $fileNames = \Yii::$app->getRequest()->post($this->removeField);

        for ($i = 0; $i < count($fileNames); $i++) {
            FileHelper::unlink($this->uploadPath . DIRECTORY_SEPARATOR . $fileNames[$i]);
        }
    }
}