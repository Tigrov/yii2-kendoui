<?php
namespace tigrov\kendoui\assets;

class KendoAsset extends \yii\web\AssetBundle
{
    public $sourcePath = null;
    public $css = [
        'http://kendo.cdn.telerik.com/2018.1.117/styles/kendo.common-bootstrap.min.css',
        'http://kendo.cdn.telerik.com/2018.1.117/styles/kendo.bootstrap.min.css',
    ];
    public $js = [
        'http://kendo.cdn.telerik.com/2018.1.117/js/kendo.all.min.js',
        //'http://kendo.cdn.telerik.com/2018.1.117/js/kendo.ui.core.min.js',
    ];
    public $depends = [
        'yii\web\JqueryAsset',
    ];
}