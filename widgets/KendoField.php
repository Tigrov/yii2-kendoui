<?php
namespace tigrov\kendoui\widgets;

use Yii;
use yii\base\ErrorHandler;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\base\Model;
use yii\web\JsExpression;

class KendoField extends \yii\widgets\ActiveField
{
    /**
     * @inheritdoc
     */
    public $template = '<div class="k-edit-label">{label}</div>\n<div class="k-edit-field">{input}\n{hint}\n{error}</div>';

    /**
     * @inheritdoc
     */
    public $errorOptions = ['tag' => 'span', 'class' => 'k-invalid-msg'];

    /**
     * @inheritdoc
     */
    public function render($content = null)
    {
        if ($content === null) {
            if (!isset($this->parts['{input}'])) {
                $this->textInput();
            }
            if (!isset($this->parts['{label}'])) {
                $this->parts['{label}'] = Html::activeLabel($this->model, $this->attribute, $this->labelOptions);
            }
            if (!isset($this->parts['{error}'])) {
                $this->errorOptions['data-for'] = $this->attribute;
                $this->parts['{error}'] = Html::error($this->model, $this->attribute, $this->errorOptions);
            }
            if (!isset($this->parts['{hint}'])) {
                $this->parts['{hint}'] = '';
            }
            $content = strtr($this->template, $this->parts);
        } elseif (!is_string($content)) {
            $content = call_user_func($content, $this);
        }

        return $this->begin() . "\n" . $content . "\n" . $this->end();
    }

    public function mergeInputOptions($config = [], $options = [])
    {
        $options = ArrayHelper::merge($this->inputOptions, $config, $options);
        if (!array_key_exists('id', $options)) {
            $options['id'] = Html::getInputId($this->model, $this->attribute);
        }
        $this->adjustLabelFor($options);

        return $options;
    }

    /**
     * @inheritdoc
     */
    public function input($type, $options = [])
    {
        $options = $this->mergeInputOptions(['class' => 'k-textbox'], $options);
        $this->parts['{input}'] = Html::input($type, $this->attribute, null, $options);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function textInput($options = [])
    {
        $options = $this->mergeInputOptions(['class' => 'k-textbox'], $options);
        $this->parts['{input}'] = Html::textInput($this->attribute, null, $options);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function hiddenInput($options = [])
    {
        $options = $this->mergeInputOptions($options);
        $this->parts['{input}'] = Html::hiddenInput($this->attribute, null, $options);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function passwordInput($options = [])
    {
        $options = $this->mergeInputOptions(['class' => 'k-textbox'], $options);
        $this->parts['{input}'] = Html::passwordInput($this->attribute, null, $options);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function fileInput($options = [])
    {
        $options = $this->mergeInputOptions($options, ['data-role' => 'upload', 'placeholder' => null]);
        $this->parts['{input}'] = Html::fileInput($this->attribute, null, $options);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function textarea($options = [])
    {
        $options = $this->mergeInputOptions(['class' => 'k-textbox'], $options);
        $this->parts['{input}'] = Html::textarea($this->attribute, null, $options);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function radio($options = [], $enclosedByLabel = true)
    {
        $options = $this->mergeInputOptions($options, ['data-bind' => 'checked:' . $this->attribute, 'placeholder' => null]);
        if ($enclosedByLabel) {
            $this->parts['{input}'] = Html::radio($this->attribute, null, $options);
            $this->parts['{label}'] = '';
        } else {
            if (isset($options['label']) && !isset($this->parts['{label}'])) {
                $this->parts['{label}'] = $options['label'];
                if (!empty($options['labelOptions'])) {
                    $this->labelOptions = $options['labelOptions'];
                }
            }
            unset($options['label'], $options['labelOptions']);
            $this->parts['{input}'] = Html::radio($this->attribute, null, $options);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function checkbox($options = [], $enclosedByLabel = true)
    {
        $options = $this->mergeInputOptions($options, ['data-bind' => 'checked:' . $this->attribute, 'placeholder' => null]);
        if ($enclosedByLabel) {
            $this->parts['{input}'] = Html::checkbox($this->attribute, null, $options);
            $this->parts['{label}'] = '';
        } else {
            if (isset($options['label']) && !isset($this->parts['{label}'])) {
                $this->parts['{label}'] = $options['label'];
                if (!empty($options['labelOptions'])) {
                    $this->labelOptions = $options['labelOptions'];
                }
            }
            unset($options['labelOptions']);
            $options['label'] = null;
            $this->parts['{input}'] = Html::checkbox($this->attribute, null, $options);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function dropDownList($items, $options = [])
    {
        $options = $this->mergeInputOptions($options, ['data-role' => 'dropdownlist', 'placeholder' => null]);
        $this->parts['{input}'] = Html::dropDownList($this->attribute, null, $items, $options);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function listBox($items, $options = [])
    {
        $options = $this->mergeInputOptions($options, ['placeholder' => null]);
        $this->parts['{input}'] = Html::listBox($this->attribute, null, $items, $options);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function checkboxList($items, $options = [])
    {
        $options = $this->mergeInputOptions($options, ['placeholder' => null]);
        $this->parts['{input}'] = Html::checkboxList($this->attribute, null, $items, $options);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function radioList($items, $options = [])
    {
        $options = $this->mergeInputOptions($options, ['placeholder' => null]);
        $this->parts['{input}'] = Html::radioList($this->attribute, null, $items, $options);

        return $this;
    }

    public function autoComplete($options = [])
    {
        $options = array_merge(['data-role' => 'autocomplete'], $options);
        return $this->textInput($options);
    }
}
