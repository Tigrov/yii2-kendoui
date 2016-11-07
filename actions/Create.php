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
                try {
                    if ($model->save()) {
                        $this->data[] = $this->getModelData($model);
                    } else {
                        $this->data[] = $item;
                        $this->addValidationErrors($model);
                    }
                } catch (\yii\db\IntegrityException $e) {
                    // one or more unique constraint violations
                    $this->data[] = $item;
                    $this->addEventErrorMessage($model, $e);
                }
            }
        }
    }
}
