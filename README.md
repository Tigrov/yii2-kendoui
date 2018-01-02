Kendo UI Extension for Yii 2
============

Currently implemented DataSource which is generating on ActiveRecord model-base.

Can be used with:
* Kendo Grid
* Kendo List
* other features that require DataSources

Installation
------------

The preferred way to install this extension is through composer.

Either run

php composer.phar require --prefer-dist tigrov/yii2-kendoui

or add

"tigrov/yii2-kendoui": "~1.0"

to the require section of your composer.json file.

Also since there is yii2-kendoui 2.0 in development now, 
make sure you have a version constraint in your composer.json, 
that does not allow it to be installed automatically on update, 
so when next major version of yii2-kendoui is released, 
your project won't break by itself. 
A version constraint that does not include 2.0 is for example ~1.0, 
make sure you do not have >= or * in version constraints in composer.json.

To try the new version of yii2-kendoui add

"tigrov/yii2-kendoui": "2.x-dev"

to the require section of your composer.json file.

See more at [Tigrov/yii2-kendoui/tree/2](https://github.com/Tigrov/yii2-kendoui/tree/2)

How to use?
------------

@app/controllers/AddressController.php
```php
use \tigrov\kendoui\KendoBuild;

class AddressController extends Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }

    public static function kendoActions()
    {
        // Address extends of ActiveRecord
        $options = [
            'model' => Address::className(),
            'query' => [
                'where' => ['status_id' => Address::STATUS_ACTIVE],
            ],
        ];

        return KendoBuild::actions($options);
    }

    public function actions()
    {
        return array_merge(parent::actions(), static::kendoActions());
    }
}
```

@app/views/address/index.php
```
<?php
use yii\helpers\Url;
use yii\helpers\Html;
use \tigrov\kendoui\DataSource;
use \tigrov\kendoui\widgets\KendoShortForm;
use \tigrov\kendoui\assets\KendoAsset;

$this->setTitle(\Yii::t('user', 'Addresses'));

KendoAsset::register($this);

$dataSource = \Yii::createObject([
    'class' => DataSource::className(),
]);
$dataSourceSettings = $dataSource->getSettings();
$dataSourceJson = json_encode($dataSourceSettings);

$this->registerJs(<<<AddressListJS
$(function () {
    var dataSourceSettings = $dataSourceJson;
    dataSourceSettings.error = function(e){console.log(e.errors)}
    var dataSource = new kendo.data.DataSource(dataSourceSettings);

    var addressList = $("#addressList").kendoListView({
        dataSource: dataSource,
        template: kendo.template($("#addresTemplate").html()),
        editTemplate: kendo.template($("#addressEditTemplate").html())
    }).data("kendoListView");

    $(".k-add-button").click(function(e) {
        addressList.add();
        e.preventDefault();
    });
});
AddressListJS
    , $this::POS_END);
?>

<p><a class="k-button k-button-icontext k-add-button" href="#"><span class="k-icon k-add"></span><?= \Yii::t('pro', 'Add new Address'); ?></a></p>
<div id="addressList"></div>

<script type="text/x-kendo-template" id="addresTemplate">
    <div class="address">
        <div class="edit-buttons">
            <a class="k-button k-button-icontext k-edit-button" href="\\#"><span class="k-icon k-edit"></span></a>
            <a class="k-button k-button-icontext k-delete-button" href="\\#"><span class="k-icon k-delete"></span></a>
        </div>
        <p>#=first_name# #=last_name#</p>
        <p>#=company#</p>
        <p>#=address#</p>
        <p>#=city#, #=postal_code#</p>
    </div>
</script>

<script type="text/x-kendo-tmpl" id="addressEditTemplate">
    <div class="address-form">
        <?php $form = KendoShortForm::begin(['dataSource' => $dataSource]); ?>
            <?= $form->kendoField('first_name'); ?>
            <?= $form->kendoField('last_name'); ?>
            <?= $form->kendoField('company'); ?>
            <?= $form->kendoField('address'); ?>
            <?= $form->kendoField('city'); ?>
            <?= $form->kendoField('postal_code'); ?>

            <div class="k-edit-buttons k-state-default">
                <a class="k-button k-button-icontext k-update-button k-primary" href="\\#"><span class="k-icon k-update"></span><?= \Yii::t('pro', 'Save'); ?></a>
                <a class="k-button k-button-icontext k-cancel-button" href="\\#"><span class="k-icon k-cancel"></span><?= \Yii::t('pro', 'Cancel'); ?></a>
            </div>
        <?php KendoShortForm::end(); ?>
    </div>
</script>
```
