<?php
/**
 * @link https://github.com/Tigrov/yii2-kendoui
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

namespace tigrov\kendoui\actions;

/**
 * Class Destroy
 *
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */
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
                    $this->afterFind($model);
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
