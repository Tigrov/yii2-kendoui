<?php
/**
 * @link https://github.com/Tigrov/yii2-kendoui
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

namespace tigrov\kendoui;

use yii\helpers\Json;

/**
 * Class Upload
 * @package tigrov\kendoui
 *
 * Kendo UI Upload properties
 * @property bool|array $async
 * @property bool $directory default: false
 * @property bool $directoryDrop default: false
 * @property string $dropZone
 * @property bool $enabled default: true
 * @property array $files
 * @property array $localization
 * @property bool $multiple default: true
 * @property bool $showFileList default: true
 * @property string $template HTML template for uploading files
 * @property array $validation
 *
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

class Upload extends Base
{
    const PARAMS = [
        'async', 'directory', 'directoryDrop', 'dropZone', 'enabled', 'files', 'localization', 'multiple',
        'showFileList', 'template', 'validation',
    ];

    public function toJSON()
    {
        $config = $this->toArray();
        $config = $this->addTemplateExpression($config);
        return Json::encode($config);
    }
}