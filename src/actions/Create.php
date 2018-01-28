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
                    $isSaved = false;
                    if ($this->beforeValidate($model) && $model->validate()) {
                        $this->afterValidate($model);
                        if ($this->beforeSave(true, $model)) {
                            $changedAttributes = array_fill_keys(array_merge(array_keys($model->getDirtyAttributes()), $model::primaryKey()), null);
                            if ($model->save(false)) {
                                $isSaved = true;
                                $this->afterSave(true, $model, $changedAttributes);
                                $response->addData($kendoData->getModelData($model));
                            }
                        }
                    }
                    if (!$isSaved) {
                        $response->addData($item);
                        $kendoData->addValidationErrors($model);
                    }
                } catch (\Exception $e) {
                    // one or more unique constraint violations
                    $response->addData($item);
                    $kendoData->addEventErrorMessage($model, $e);
                }
            }
        }
    }
}
