<?php
namespace tigrov\kendoui\components;

/**
 * AfterSaveEvent represents the information available in [[ActiveRecord::EVENT_AFTER_INSERT]] and [[ActiveRecord::EVENT_AFTER_UPDATE]].
 */
class AfterSaveEvent extends BaseEvent
{
    /**
     * @var array The attribute values that had changed and were saved.
     */
    public $changedAttributes;
}