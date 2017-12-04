<?php
namespace tigrov\kendoui\actions;

class Update extends Action
{
    public function process()
    {
        $kendoData = $this->getKendoData();
        $response = $kendoData->getResponse();
        $data = $kendoData->getRequest()->getModels();

        if ($data && is_array($data)) {
            foreach ($data as $item) {
                if ($model = $kendoData->findModel($item)) {
                    $model->setAttributes($item);
                    if ($model->save()) {
                        $response->addData($kendoData->getModelData($model));
                    } else {
                        $response->addData($item);
                        $kendoData->addValidationErrors($model);
                    }
                } else {
                    $response->addData($item);
                    $response->addError('Model not found!');
                }
            }
        }
    }
}
