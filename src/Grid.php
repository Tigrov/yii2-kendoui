<?php
/**
 * @link https://github.com/Tigrov/yii2-kendoui
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

namespace tigrov\kendoui;

use yii\helpers\Json;

/**
 * Class Grid
 * @package tigrov\kendoui
 *
 * Kendo UI Grid properties
 * @property bool|array $allowCopy default: false
 * @property bool $autoBind default: true
 * @property int $columnResizeHandleWidth default: 3
 * @property array $columns
 * @property bool|array $columnMenu default: false
 * @property DataSource $dataSource
 * @property bool|array|string $editable default: false
 * @property array $excel
 * @property bool|array $filterable default: false
 * @property bool|array $groupable default: false
 * @property int|string $height
 * @property array $messages
 * @property bool|string $mobile default: false
 * @property bool $navigatable default: false
 * @property bool|array $noRecords default: false
 * @property bool|array $pageable default: false
 * @property array $pdf
 * @property bool $persistSelection default:false
 * @property bool $reorderable default:false
 * @property bool $resizable default:false
 * @property bool|array $scrollable default: true
 * @property bool|string $selectable default: false
 * @property bool|array $sortable default: false
 * @property string|array $toolbar HTML template or config
 * @property string $rowTemplate HTML template
 * @property string $altRowTemplate HTML template
 * @property string $detailTemplate HTML template
 *
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

class Grid extends Base
{
    const PARAMS = [
        'allowCopy', 'altRowTemplate', 'autoBind', 'columnResizeHandleWidth', 'columns', 'columnMenu', 'dataSource',
        'detailTemplate', 'editable', 'excel', 'filterable', 'groupable', 'height', 'messages', 'mobile', 'navigatable',
        'noRecords', 'pageable', 'pdf', 'persistSelection', 'reorderable', 'resizable', 'rowTemplate', 'scrollable',
        'selectable', 'sortable', 'toolbar',
    ];

    public function toJSON()
    {
        $config = $this->toArray();
        $config = $this->addDataSourceExpression($config);
        $config = $this->addTemplateExpression($config, 'toolbar');
        $config = $this->addTemplateExpression($config, 'rowTemplate');
        $config = $this->addTemplateExpression($config, 'altRowTemplate');
        $config = $this->addTemplateExpression($config, 'detailTemplate');

        return Json::encode($config);
    }

    /**
     * @param BaseDataSource $dataSource
     * @param string[]|null $attributes
     * @return array
     */
    public static function columns($dataSource, $attributes = null)
    {
        $kendoData = $dataSource->getKendoData();
        $model = $kendoData->getModelInstance();

        if (!$attributes) {
            $attributes = array_keys($dataSource->getModel()['fields']);
        }

        $columns = [];
        foreach ($attributes as $attribute) {
            $columns[] = [
                'field' => $attribute,
                'title' => $model->getAttributeLabel($attribute),
            ];
        }

        return $columns;
    }
}