<?php
namespace tigrov\kendoui\assets;

class KendoAsset extends \yii\web\AssetBundle
{
    public $sourcePath = null;
    public $css = [
        'http://kendo.cdn.telerik.com/2017.3.1026/styles/kendo.common-bootstrap.min.css',
        'http://kendo.cdn.telerik.com/2017.3.1026/styles/kendo.bootstrap.min.css',
    ];
    public $js = [
        'http://kendo.cdn.telerik.com/2017.3.1026/js/kendo.all.min.js',
        //'http://kendo.cdn.telerik.com/2017.3.1026/js/kendo.ui.core.min.js',
    ];
    public $depends = [
        'yii\web\JqueryAsset',
    ];
}