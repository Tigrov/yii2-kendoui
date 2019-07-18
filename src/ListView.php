<?php
/**
 * @link https://github.com/Tigrov/yii2-kendoui
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

namespace tigrov\kendoui;

use yii\helpers\Json;

/**
 * Class ListView
 * @package tigrov\kendoui
 *
 * Kendo UI ListView properties
 * @property bool $autoBind default: true
 * @property DataSource $dataSource
 * @property int|string $height
 * @property bool|string $scrollable default: false
 * @property bool $navigatable default: false
 * @property bool|string $selectable default: false
 * @property string $template HTML template
 * @property string $altTemplate HTML template
 * @property string $editTemplate HTML template
 *
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

class ListView extends Base
{
    const PARAMS = [
        'autoBind', 'dataSource', 'height', 'scrollable', 'navigatable', 'selectable',
        'template', 'altTemplate', 'editTemplate',
    ];

    public function toJSON()
    {
        $config = $this->toArray();
        $config = $this->addDataSourceExpression($config);
        $config = $this->addTemplateExpression($config);
        $config = $this->addTemplateExpression($config, 'altTemplate');
        $config = $this->addTemplateExpression($config, 'editTemplate');

        return Json::encode($config);
    }
}