<?php
namespace tigrov\kendoui\actions;

class Create extends Action {
    public function run()
    {
        $this->createData();

        return parent::run();
    }

    public function createData()
    {
        $data = $this->getRequestModels();

        if ($data && is_array($data)) {
            foreach ($data as $item) {
                $model = $this->getModelInstance(true);
                $model->setAttributes($item);
                if ($model->save()) {
                    $this->data[] = $this->getModelData($model);
                } else {
                    $this->data[] = $item;
                    $this->addValidationErrors($model);
                }
            }
        }
    }
}
