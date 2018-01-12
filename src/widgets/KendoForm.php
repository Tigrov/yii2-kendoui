<?php
namespace tigrov\kendoui\widgets;

use Yii;
use yii\base\InvalidCallException;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use tigrov\kendoui\DataSource;
use yii\helpers\Html;

class KendoForm extends \yii\widgets\ActiveForm
{
    /**
     * @var string the default field class name when calling [[field()]] to create a new field.
     * @see fieldConfig
     */
    public $fieldClass = 'tigrov\kendoui\widgets\KendoField';

    /**
     * @var DataSource
     */
    public $dataSource;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (!isset($this->options['id'])) {
            $this->options['id'] = $this->getId();
        }

        echo Html::beginTag('div', $this->options);
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        if (!empty($this->_fields)) {
            throw new InvalidCallException('Each beginField() should have a matching endField() call.');
        }

        echo Html::endTag('div');
    }

    /**
     * @inheritdoc
     */
    public function field($model, $attribute, $options = [])
    {
        return $this->kendoField($attribute, $options, $model);
    }

    /**
     * Generates a form field.
     * A form field is associated with a model and an attribute. It contains a label, an input and an error message
     * and use them to interact with end users to collect their inputs for the attribute.
     * @param Model $model the data model
     * @param string $attribute the attribute name or expression. See [[Html::getAttributeName()]] for the format
     * about attribute expression.
     * @param array $options the additional configurations for the field object
     * @return KendoField the created ActiveField object
     * @see fieldConfig
     */
    public function kendoField($attribute, $options = [], $model = null)
    {
        $model = $model ?: $this->getModelInstance();
        $config = $this->fieldConfig;
        if ($config instanceof \Closure) {
            $config = call_user_func($config, $model, $attribute);
        }
        $config = $this->extendOptions($attribute, ArrayHelper::merge($config, $options));

        if (!isset($config['class'])) {
            $config['class'] = $this->fieldClass;
        }
        return Yii::createObject(ArrayHelper::merge($config, [
            'model' => $model,
            'attribute' => $attribute,
            'form' => $this,
        ]));
    }

    /**
     * Begins a form field.
     * This method will create a new form field and returns its opening tag.
     * You should call [[endField()]] afterwards.
     * @param string $attribute the attribute name or expression. See [[Html::getAttributeName()]] for the format
     * about attribute expression.
     * @param array $options the additional configurations for the field object
     * @return string the opening tag
     * @see endField()
     * @see field()
     */
    public function beginKendoField($attribute, $options = [])
    {
        $field = $this->kendoField($attribute, $options);
        $this->_fields[] = $field;
        return $field->begin();
    }

    /**
     * @return \yii\db\ActiveRecord
     */
    public function getModelInstance()
    {
        static $model;
        if ($model === null) {
            $model = $this->dataSource->getKendoData()->getModelInstance();
        }

        return $model;
    }

    public function extendOptions($attribute, $options)
    {
        $options = $this->expandOptionsType($attribute, $options);
        $options = $this->expandOptionsValidation($attribute, $options);

        $options['inputOptions']['data-bind'] = 'value:' . $attribute;

        return $options;
    }

    public function expandOptionsType($attribute, $options)
    {
        $dataSource = $this->dataSource->toArray();
        $columns = $this->getModelInstance()->getTableSchema()->columns;
        if (isset($dataSource['schema']['model']['fields'][$attribute]['type'])) {
            $options['inputOptions']['data-type'] = $dataSource['schema']['model']['fields'][$attribute]['type'];
        }

        if (!empty($columns[$attribute])) {
            if ($role = static::convertTypeToRole($columns[$attribute]->type)) {
                $options['inputOptions']['data-role'] = $role;
            }
        }

        return $options;
    }

    public function expandOptionsValidation($attribute, $options)
    {
        $dataSource = $this->dataSource->toArray();
        if (!empty($dataSource['schema']['model']['fields'][$attribute]['validation'])) {
            $validation = $dataSource['schema']['model']['fields'][$attribute]['validation'];

            foreach ($validation as $rule => $value) {
                $ruleVal = $rule;
                switch ($rule) {
                    case 'email':
                    case 'url':
                        $rule = 'type';
                    case 'required':
                        $value = $ruleVal;
                    case 'pattern':
                    case 'min':
                    case 'max':
                    case 'step':
                        if (!isset($options['inputOptions'][$rule])) {
                            $options['inputOptions'][$rule] = $value;
                        }
                }
            }
        }

        return $options;
    }

    /**
     * @param string $type
     * @return string
     */
    public static function convertTypeToRole($type)
    {
        switch ($type) {
            case 'smallint':
            case 'integer':
            case 'bigint':
            case 'float':
            case 'decimal':
                return 'numerictextbox';
            case 'datetime':
            case 'timestamp':
                return 'datepicker';
            case 'date':
                return 'datetimepicker';
            case 'time':
                return 'timePicker';
        }

        return null;
    }
}
