<?php
namespace tigrov\kendoui\actions;

class Destroy extends Action
{
    public function process()
    {
        $kendoData = $this->getKendoData();
        $response = $kendoData->getResponse();
        $data = $kendoData->getRequest()->getModels();

        if ($data && is_array($data)) {
            foreach ($data as $item) {
                if ($model = $kendoData->findModel($item)) {
                    if ($this->beforeDelete($model) && $model->delete()) {
                        $this->afterDelete($model);
                    } else {
                        $response->addData($item);
                        $pk = $kendoData->getKeyValue($model);
                        $response->addError('Failed to remove the row {pk}', ['pk' => $pk]);
                    }
                } else {
                    $response->addData($item);
                    $response->addError('Model not found!');
                }
            }
        }
    }
}
