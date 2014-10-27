<?php
namespace tigrov\kendoui\assets;

class KendouiAsset extends \yii\web\AssetBundle
{
    public $sourcePath = null;
    public $css = [
        'http://cdn.kendostatic.com/2014.2.1008/styles/kendo.common-bootstrap.min.css',
        'http://cdn.kendostatic.com/2014.2.1008/styles/kendo.bootstrap.min.css',
    ];
    public $js = [
        'http://cdn.kendostatic.com/2014.2.1008/js/kendo.ui.all.min.js',
    ];
    public $depends = [
        'yii\web\JqueryAsset',
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
} 