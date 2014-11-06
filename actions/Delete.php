<?php
namespace tigrov\kendoui\actions;

class Delete extends Action {
    public function run()
    {
        $this->deleteData();

        return $this->getResponseData();
    }

    public function deleteData()
    {
        $data = $this->getRequestModels();

        if ($data && is_array($data)) {
            foreach ($data as $item) {
                if ($model = $this->findModel($item)) {
                    if (!$model->delete()) {
                        $this->data[] = $item;
                        $pk = implode($this->keySeparator, $model->getPrimaryKey(true));
                        $this->addError($this->t('Failed to remove the row {pk}', ['pk' => $pk]));
                    }
                } else {
                    $this->data[] = $item;
                    $this->addError(static::t('Model not found!'));
                }
            }
        }
    }
}
