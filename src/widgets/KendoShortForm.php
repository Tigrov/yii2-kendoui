<?php
namespace tigrov\kendoui\widgets;

class KendoShortForm extends KendoForm
{
    /**
     * @inheritdoc
     */
    public $fieldConfig = ['template' => '{input}{hint}'];

    /**
     * @inheritdoc
     */
    public function extendOptions($attribute, $options)
    {
        $options = parent::extendOptions($attribute, $options);

        $labels = $this->dataSource->getKendoData()->getLabels();
        if (!empty($labels[$attribute])) {
            $options['inputOptions']['placeholder'] = $labels[$attribute];
        }

        return $options;
    }
}
