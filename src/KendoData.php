<?php
/**
 * @link https://github.com/Tigrov/yii2-kendoui
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

namespace tigrov\kendoui;

use tigrov\kendoui\helpers\DataSourceHelper;
use yii\base\InvalidConfigException;
use yii\base\BaseObject;
use yii\db\Schema;

/**
 * Class KendoData
 *
 * @property array $model settings for model (class, scenario, default attribute values and etc.).
 * Using for \Yii::createObject($model)
 * @property array $query setting additional conditions, joins and etc.
 * Using for \Yii::configure($modelClass::find(), $query)
 * @property string[] $attributeNames names of attributes that must be included into result (default all)
 * @property string[] $exceptAttributes names of attributes that must be excepted from result (default empty)
 * @property string[] $extraFields additional fields from ActiveRecord::extraFields() (default empty)
 * @property string $keySeparator multiple key separator (default "__")
 * @property bool $extendMode indicates the mode of model data read.
 * If true reading data from ActiveRecord::toArray() else from ActiveRecord::getAttributes().
 * Use true if your KendoUI DataSource field names from ActiveRecord::fields() is not equal to db attribute names
 * or you are using extra fields. (default false)
 *
 * @property-read string $keyField composite key field
 * @property-read array $attributes available attributes of the model
 * @property-read string $modelClass class of the model
 * @property-read \yii\db\ActiveRecord $modelInstance created object of the model. Result of \Yii::createObject($model)
 * @property-read \yii\db\ActiveQuery $activeQuery active query object for the model
 * Result of \Yii::configure($modelClass::find(), $query)
 * @property-read \tigrov\kendoui\components\Request $request
 * @property-read \tigrov\kendoui\components\Response $response
 *
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */
class KendoData extends BaseObject
{
    public $model;
    public $query;
    public $attributeNames;
    public $exceptAttributes = [];
    public $extraFields = [];
    public $keySeparator = DataSourceHelper::DEFAULT_KEY_SEPARATOR;

    /**
     * @var boolean indicates the mode of model data read.
     * Use true if your KendoUI field names is not equal to db attribute names or you are using extra fields.
     * If $extraFields is not empty then $extendMode is equal true.
     * If true then used ActiveRecord::toArray() for every row.
     */
    private $_extendMode = false;

    /** @var string */
    private $_modelClass;

    /** @var \yii\db\ActiveRecord */
    private $_modelInstance;

    /** @var \yii\db\ActiveQuery */
    private $_activeQuery;

    /** @var \tigrov\kendoui\components\Request */
    private $_request;

    /** @var \tigrov\kendoui\components\Response */
    private $_response;

    /** @var array */
    private $_labels;

    public function __construct($config = [])
    {
        $this->preInit($config);

        parent::__construct($config);
    }

    public function preInit(&$config)
    {
        if (!isset($config['model']) || !is_string($config['model']) && empty($config['model']['class'])) {
            throw new InvalidConfigException('Model configuration must be an array containing a "class" element.');
        }

        foreach ($this->components() as $id => $component) {
            if (!isset($config[$id])) {
                $config[$id] = $component;
            } elseif (is_array($config[$id]) && !isset($config[$id]['class'])) {
                $config[$id]['class'] = $component['class'];
            }
        }
    }

    public function components()
    {
        return [
            'request' => ['class' => 'tigrov\kendoui\components\Request'],
            'response' => ['class' => 'tigrov\kendoui\components\Response'],
        ];
    }

    public function setRequest($config)
    {
        $this->_request = \Yii::createObject($config);
    }

    /**
     * @return \tigrov\kendoui\components\Request
     */
    public function getRequest()
    {
        return $this->_request;
    }

    public function setResponse($config)
    {
        $this->_response = \Yii::createObject($config);
    }

    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * @return \yii\db\ActiveRecord
     */
    public function getModelClass()
    {
        if ($this->_modelClass === null) {
            $this->_modelClass = is_string($this->model) ? $this->model : $this->model['class'];
        }

        return $this->_modelClass;
    }

    /**
     * @return \yii\db\ActiveRecord
     */
    public function getModelInstance($isNew = false)
    {
        if ($isNew || $this->_modelInstance === null) {
            $this->_modelInstance = \Yii::createObject($this->model);
        }

        return $this->_modelInstance;
    }

    /**
     * @return \yii\db\ActiveRecord
     */
    public function findModel($data)
    {
        $modelClass = $this->getModelClass();
        $keys = $modelClass::primaryKey();
        $keysData = array_intersect_key($data, array_flip($keys));
        if (count($keysData) != count($keys)) {
            $pk = $this->composeKey($keys);
            if (!isset($data[$pk])) {
                $this->response->addError('Request must contain keys: {keys} or complex key {pk}', ['keys' => implode(', ', $keys), 'pk' => $pk]);
                return null;
            }

            $keysData = array_combine($keys, explode($this->keySeparator, $data[$pk], count($keys)));
        }

        $query = $this->getActiveQuery();
        $query->andWhere($keysData);

        $model = $query->one();
        if ($model && is_array($this->model) && isset($this->model['scenario'])) {
            $model->scenario = $this->model['scenario'];
        }

        return $model;
    }

    public function setExtendMode($value)
    {
        return $this->_extendMode = $value;
    }

    public function getExtendMode()
    {
        return $this->_extendMode || $this->extraFields;
    }

    public function composeKey($keys)
    {
        return implode($this->keySeparator, $keys);
    }

    public function getKeyField()
    {
        $modelClass = $this->getModelClass();
        return $this->composeKey($modelClass::primaryKey());
    }

    /**
     * @param \yii\db\ActiveRecord $model
     * @return string
     */
    public function getKeyValue($model)
    {
        return $this->composeKey($model->getPrimaryKey(true));
    }

    /**
     * @param $model \yii\db\ActiveRecord
     * @return array
     */
    public function getModelData($model)
    {
        $data = $this->getExtendMode()
            ? $model->toArray($this->getAttributes() ?: [], $this->extraFields)
            : $model->getAttributes($this->attributeNames ?: null, $this->exceptAttributes);

        $pk = $this->getKeyField();
        if (empty($data[$pk])) {
            $data[$pk] = $this->getKeyValue($model);
        }

        return $this->prepareDatesToJs([$data])[0];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getActiveQuery()
    {
        if ($this->_activeQuery === null) {
            $modelClass = $this->getModelClass();
            $this->_activeQuery = $modelClass::find();
            if ($this->query) {
                \Yii::configure($this->_activeQuery, $this->query);
            }
        }

        return $this->_activeQuery;
    }

    /**
     * @param \yii\db\ActiveRecord $model
     */
    public function addValidationErrors($model)
    {
        $this->response->addValidationErrors([$this->getKeyValue($model) => $model->getErrors()]);
    }

    /**
     * @param \yii\db\ActiveRecord $model
     * @param \Exception $event
     */
    public function addEventErrorMessage($model, $event)
    {
        $this->response->addValidationErrors([$this->getKeyValue($model) => [$this->getKeyField() => $event->getMessage()]]);
    }

    /**
     * @param bool $autoFill fill attribute names if $attributeNames is empty
     * @return array
     */
    public function getAttributes($autoFill = false)
    {
        $attributes = $this->attributeNames !== null || !$autoFill && !$this->exceptAttributes
            ? $this->attributeNames
            : $this->getModelInstance()->attributes();

        if ($this->exceptAttributes) {
            $attributes = array_diff($attributes, $this->exceptAttributes);
        }

        return $attributes;
    }

    public function toModelArray($rows)
    {
        $attributes = $this->getAttributes() ?: [];
        $extraFields = $this->extraFields;
        $pk = $this->getKeyField();

        $data = [];
        foreach ($rows as $i => $row) {
            $model = $this->getModelInstance(true);
            $model::populateRecord($model, $row);
            $model->afterFind();
            $data[$i] = $model->toArray($attributes, $extraFields);
            if (empty($data[$i][$pk])) {
                $data[$i][$pk] = $this->getKeyValue($model);
            }
        }

        return $this->prepareDatesToJs($data);
    }

    public function filterAttributes($rows)
    {
        $attributes = $this->getAttributes();
        $modelClass = $this->getModelClass();
        $keys = $modelClass::primaryKey();

        $data = [];
        if (!$attributes) {
            $data = $rows;
        } else {
            $attributesFlip = array_flip($attributes);
            foreach ($rows as $i => $row) {
                $data[$i] = array_intersect_key($row, $attributesFlip);
            }
        }

        if (count($keys) > 1) {
            $pk = $this->composeKey($keys);
            $keysFlip = array_flip($keys);
            foreach ($rows as $i => $row) {
                $data[$i][$pk] = $this->composeKey(array_intersect_key($row, $keysFlip));
            }
        }

        return $this->prepareDatesToJs($data);
    }

    protected function prepareDatesToJs($data)
    {
        $attributes = $this->getAttributes(true);
        $modelClass = $this->getModelClass();
        $columns = $modelClass::getTableSchema()->columns;

        $dateTypes = [Schema::TYPE_DATETIME, Schema::TYPE_TIMESTAMP, Schema::TYPE_DATE, Schema::TYPE_TIME];
        foreach ($attributes as $attr) {
            if (isset($columns[$attr]) && in_array($columns[$attr]->type, $dateTypes)) {
                for ($i = 0; $i < count($data); ++$i) {
                    if (!empty($data[$i][$attr])) {
                        $data[$i][$attr] = DataSourceHelper::convertDateToJs($data[$i][$attr], $columns[$attr]->type);
                    }
                }
            }
        }

        return $data;
    }

    public function prepareDatesToDb($data)
    {
        $attributes = $this->getAttributes(true);
        $modelClass = $this->getModelClass();
        $columns = $modelClass::getTableSchema()->columns;

        $dateTypes = [Schema::TYPE_INTEGER, Schema::TYPE_BIGINT, Schema::TYPE_DATETIME, Schema::TYPE_TIMESTAMP,
            Schema::TYPE_DATE, Schema::TYPE_TIME];
        foreach ($attributes as $attr) {
            if (isset($columns[$attr]) && in_array($columns[$attr]->type, $dateTypes)) {
                for ($i = 0; $i < count($data); ++$i) {
                    if (!empty($data[$i][$attr])) {
                        $data[$i][$attr] = DataSourceHelper::convertDateToDb($data[$i][$attr], $columns[$attr]->type)
                            ?: $data[$i][$attr];
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Labels for forms and other
     * @return array
     */
    public function getLabels()
    {
        if ($this->_labels === null) {
            $modelInstance = $this->getModelInstance();
            $labels = $modelInstance->attributeLabels();
            if ($this->getExtendMode()) {
                $attributes = $this->getAttributes(true);
                $extraFields = $this->extraFields;
                $fields = array_merge(
                    array_combine($attributes, $attributes),
                    $modelInstance->fields(),
                    array_combine($extraFields, $extraFields));

                $this->_labels = [];
                foreach ($fields as $name => $field) {
                    if (isset($labels[$name])) {
                        $this->_labels[$field] = $labels[$name];
                    }
                }
            } else {
                $this->_labels = $labels;
            }
        }

        return $this->_labels;
    }
}