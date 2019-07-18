<?php
/**
 * @link https://github.com/Tigrov/yii2-kendoui
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

namespace tigrov\kendoui\assets;

/**
 * Class KendoAsset
 *
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */
class KendoAsset extends \yii\web\AssetBundle
{
    public $sourcePath = null;
    public $css = [
        'https://kendo.cdn.telerik.com/2019.2.619/styles/kendo.common-bootstrap.min.css',
        'https://kendo.cdn.telerik.com/2019.2.619/styles/kendo.bootstrap.min.css',
    ];
    public $js = [
        'https://kendo.cdn.telerik.com/2019.2.619/js/kendo.all.min.js',
        //'https://kendo.cdn.telerik.com/2019.2.619/js/kendo.ui.core.min.js',
    ];
    public $depends = [
        'yii\web\JqueryAsset',
    ];
}