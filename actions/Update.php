<?php
namespace tigrov\kendoui\actions;

class Update extends Action
{
    public function run()
    {
        $this->updateData();

        return $this->getResponseData();
    }

    public function updateData()
    {
        $data = $this->getRequestModels();

        if ($data && is_array($data)) {
            foreach ($data as $item) {
                if ($model = $this->findModel($item)) {
                    $model->setAttributes($item);
                    if ($model->save()) {
                        $this->data[] = $this->getModelData($model);
                    } else {
                        $this->data[] = $item;
                        $this->addValidationErrors($model);
                    }
                } else {
                    $this->data[] = $item;
                    $this->addError($this->t('Model not found!'));
                }
            }
        }
    }
}
