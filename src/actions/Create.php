<?php
namespace tigrov\kendoui\actions;

class Create extends Action
{
    public function process()
    {
        $kendoData = $this->getKendoData();
        $response = $kendoData->getResponse();
        $data = $kendoData->getRequest()->getModels();

        if ($data && is_array($data)) {
            foreach ($data as $item) {
                $model = $kendoData->getModelInstance(true);
                $model->setAttributes($item);
                try {
                    if ($model->save()) {
                        $response->addData($kendoData->getModelData($model));
                    } else {
                        $response->addData($item);
                        $kendoData->addValidationErrors($model);
                    }
                } catch (\yii\db\IntegrityException $e) {
                    // one or more unique constraint violations
                    $response->addData($item);
                    $kendoData->addEventErrorMessage($model, $e);
                }
            }
        }
    }
}
