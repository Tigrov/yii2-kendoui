<?php
/**
 * @link https://github.com/Tigrov/yii2-kendoui
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

namespace tigrov\kendoui\actions;

/**
 * Class Update
 *
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */
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
                    $isSaved = false;
                    $model->setAttributes($item);
                    if ($this->beforeValidate($model) && $model->validate()) {
                        $this->afterValidate($model);
                        if ($this->beforeSave(false, $model)) {
                            $changedAttributes = array_merge(
                                array_fill_keys(array_keys($model->getDirtyAttributes()), null),
                                $model->getOldAttributes()
                            );
                            if ($model->save(false)) {
                                $isSaved = true;
                                $this->afterSave(false, $model, $changedAttributes);
                                $response->addData($kendoData->getModelData($model));
                            }
                        }
                    }
                    if (!$isSaved) {
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
