<?php
namespace tigrov\kendoui\actions;

use tigrov\kendoui\builders\KendoDataBuilder;
use tigrov\kendoui\components\AfterSaveEvent;
use tigrov\kendoui\components\BaseEvent;
use tigrov\kendoui\components\ModelEvent;
use tigrov\kendoui\KendoData;
use yii\db\ActiveRecord;

abstract class Action extends \yii\base\Action
{
    /** @var KendoData */
    private $_kendoData;

    /**
     * @inheritdoc
     */
    public function run()
    {
        \Yii::$app->getResponse()->format = $this->getKendoData()->getResponse()->format;

        $this->process();

        return $this->getResult();
    }

    public function setKendoData($config)
    {
        $this->_kendoData = KendoDataBuilder::build($config);
    }

    public function getKendoData()
    {
        return $this->_kendoData;
    }

    public function getResult()
    {
        return $this->getKendoData()->getResponse()->getResult();
    }

    abstract public function process();

    /**
     * This method is called at the beginning of inserting or updating a record.
     *
     * The default implementation will trigger an [[EVENT_BEFORE_INSERT]] event when `$insert` is `true`,
     * or an [[EVENT_BEFORE_UPDATE]] event if `$insert` is `false`.
     * When overriding this method, make sure you call the parent implementation like the following:
     *
     * ```php
     * public function beforeSave($insert, $model)
     * {
     *     if (!parent::beforeSave($insert, $model)) {
     *         return false;
     *     }
     *
     *     // ...custom code here...
     *     return true;
     * }
     * ```
     *
     * @param bool $insert whether this method called while inserting a record.
     * If `false`, it means the method is called while updating a record.
     * @param ActiveRecord $model
     * @return bool whether the insertion or updating should continue.
     * If `false`, the insertion or updating will be cancelled.
     */
    public function beforeSave($insert, $model)
    {
        $event = new ModelEvent([
            'model' => $model,
        ]);
        $this->trigger($insert ? ActiveRecord::EVENT_BEFORE_INSERT : ActiveRecord::EVENT_BEFORE_UPDATE, $event);

        return $event->isValid;
    }

    /**
     * This method is called at the end of inserting or updating a record.
     * The default implementation will trigger an [[EVENT_AFTER_INSERT]] event when `$insert` is `true`,
     * or an [[EVENT_AFTER_UPDATE]] event if `$insert` is `false`. The event class used is [[AfterSaveEvent]].
     * When overriding this method, make sure you call the parent implementation so that
     * the event is triggered.
     * @param bool $insert whether this method called while inserting a record.
     * If `false`, it means the method is called while updating a record.
     * @param ActiveRecord $model
     * @param array $changedAttributes The old values of attributes that had changed and were saved.
     * You can use this parameter to take action based on the changes made for example send an email
     * when the password had changed or implement audit trail that tracks all the changes.
     * `$changedAttributes` gives you the old attribute values while the active record (`$this`) has
     * already the new, updated values.
     *
     * Note that no automatic type conversion performed by default. You may use
     * [[\yii\behaviors\AttributeTypecastBehavior]] to facilitate attribute typecasting.
     * See http://www.yiiframework.com/doc-2.0/guide-db-active-record.html#attributes-typecasting.
     */
    public function afterSave($insert, $model, $changedAttributes)
    {
        $this->trigger($insert ? ActiveRecord::EVENT_AFTER_INSERT : ActiveRecord::EVENT_AFTER_UPDATE, new AfterSaveEvent([
            'model' => $model,
            'changedAttributes' => $changedAttributes,
        ]));
    }

    /**
     * This method is invoked before deleting a record.
     *
     * The default implementation raises the [[EVENT_BEFORE_DELETE]] event.
     * When overriding this method, make sure you call the parent implementation like the following:
     *
     * ```php
     * public function beforeDelete($model)
     * {
     *     if (!parent::beforeDelete($model)) {
     *         return false;
     *     }
     *
     *     // ...custom code here...
     *     return true;
     * }
     * ```
     *
     * @param ActiveRecord $model
     * @return bool whether the record should be deleted. Defaults to `true`.
     */
    public function beforeDelete($model)
    {
        $event = new ModelEvent([
            'model' => $model,
        ]);
        $this->trigger(ActiveRecord::EVENT_BEFORE_DELETE, $event);

        return $event->isValid;
    }

    /**
     * This method is invoked after deleting a record.
     * The default implementation raises the [[EVENT_AFTER_DELETE]] event.
     * You may override this method to do postprocessing after the record is deleted.
     * Make sure you call the parent implementation so that the event is raised properly.
     * @param ActiveRecord $model
     */
    public function afterDelete($model)
    {
        $this->trigger(ActiveRecord::EVENT_AFTER_DELETE, new BaseEvent(['model' => $model]));
    }
}