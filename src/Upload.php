<?php
/**
 * @link https://github.com/Tigrov/yii2-kendoui
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

namespace tigrov\kendoui;

/**
 * Class DataSource
 * @package tigrov\kendoui
 *
 * @property KendoData $kendoData
 * @property-read array $model
 *
 * Kendo UI DataSource properties
 * @property bool|array $async
 * @property bool $directory
 * @property bool $directoryDrop
 * @property string $dropZone
 * @property bool $enabled
 * @property array $files
 * @property array $localization
 * @property bool $multiple
 * @property bool $showFileList
 * @property string $template
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
}