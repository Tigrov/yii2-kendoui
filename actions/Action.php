<?php
namespace tigrov\kendoui\actions;

use yii\base\InvalidConfigException;
use yii\web\Response;

/**
 * @property array $model settings for model (class, scenario, default attribute values and etc.).
 * Using for \Yii::createObject($model)
 * @property array $query setting additional conditions, joins and etc.
 * Using for \Yii::configure($modelClass::find(), $query)
 * @property array $attributeNames names of attributes that must be included into result (default all)
 * @property array $exceptAttributes names of attributes that must be excepted from result (default empty)
 * @property array $extraFields additional fields from ActiveRecord::extraFields() (default empty)
 * @property string $keySeparator multiple key separator (default "__")
 * @property string $responseFormat multiple key separator (default Response::FORMAT_JSON)
 * @property bool $extendMode indicates the mode of model data read.
 * If true reading data from ActiveRecord::toArray() else from ActiveRecord::getAttributes().
 * Use true if your KendoUI DataSource field names from ActiveRecord::fields() is not equal to db attribute names
 * or you are using extra fields. (default false)
 * @property string $translateCategory translate category for Yii::t() (default "kendoui")
 * @property-read string $modelClass class of model
 * @property-read \yii\db\ActiveRecord $modelInstance created object of model. Result of \Yii::createObject($model)
 * @property-read \yii\db\ActiveQuery $activeQuery active query object for model.
 * Result of \Yii::configure($modelClass::find(), $query)
 * @property-read array $errors list of errors
 * @property array $requestData data from request (default $_POST ?: $_GET)
 * @property-read array $responseData data for response
 * @property array $requestParams key => value request parameters names specified by DataSource.transport.parameterMap
 * (default: take, skip, page, pageSize, filter, sort, models, group, aggregate)
 * @property array $responseParams key => value response parameters names specified by DataSource.schema
 * (default: data, total, errors, groups, aggregates)
 */

abstract class Action extends \yii\base\Action
{
    public $model;
    public $query;
    public $attributeNames = [];
    public $exceptAttributes = [];
    public $extraFields = [];
    public $keySeparator = '__';
    public $responseFormat = Response::FORMAT_JSON;

    /**
     * @var bool indicates the mode of model data read.
     * Use true if your KendoUI field names is not equal to db attribute names or you are using extra fields.
     * If $extraFields is not empty then $extendMode is equal true.
     * If true then used ActiveRecord::toArray() for every row.
     */
    public $_extendMode = false;

    public $translateCategory = 'kendoui';

    private $_modelClass;
    private $_modelInstance;
    private $_activeQuery;

    protected $data = [];
    private $_errors = [];

    private $_requestData;
    private $_responseData;

    /**
     * @var array request params for result manipulation (limit, offset, filter, sort and etc.)
     * specified by DataSource.transport.parameterMap
     */
    private $_requestParams = [
        /* Limit */
        'take' => 'take',
        /* Offset */
        'skip' => 'skip',
        /* Page number */
        'page' => 'page',
        /* Rows per page */
        'pageSize' => 'pageSize',
        'filter' => 'filter',
        'sort' => 'sort',
        'models' => 'models',
        'group' => 'group',
        'aggregate' => 'aggregate',
    ];

    /**
     * @var array response params for result (limit, offset, filter, sort and etc.)
     * specified by DataSource.schema
     */
    private $_responseParams = [
        'data' => 'data',
        'total' => 'total',
        'errors' => 'errors',
        'groups' => 'groups',
        'aggregates' => 'aggregates',
    ];

    public function init()
    {
        parent::init();
        $this->registerTranslations();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        \Yii::$app->response->format = $this->responseFormat;

        return $this->getResponseData();
    }

    public function registerTranslations()
    {
        if (\Yii::$app->has('i18n')) {
            $i18n = \Yii::$app->i18n;
            $i18n->translations[$this->translateCategory] = [
                'class' => 'yii\i18n\PhpMessageSource',
            ];
        }
    }

    public function t($message, $params = [])
    {
        return \Yii::$app->has('i18n')
            ? \Yii::t($this->translateCategory, $message, $params)
            : $message;
    }

    /**
     * @return \yii\db\ActiveRecord
     */
    public function getModelClass()
    {
        if ($this->_modelClass === null) {
            if (!$this->model || !is_string($this->model) && empty($this->model['class'])) {
                throw new InvalidConfigException($this->t('Model configuration must be an array containing a "class" element.'));
            }
            $this->_modelClass = is_string($this->model) ? $this->model : $this->model['class'];
        }

        return $this->_modelClass;
    }

    /**
     * @return \yii\db\ActiveRecord
     */
    public function getModelInstance($isNew = false)
    {
        if ($isNew || $this->_modelInstance === null) {
            $this->_modelInstance = \Yii::createObject($this->model);
        }

        return $this->_modelInstance;
    }

    /**
     * @return \yii\db\ActiveRecord
     */
    public function findModel($data)
    {
        $modelClass = $this->getModelClass();
        $keys = $modelClass::primaryKey();

        $keysData = array_intersect_key($data, array_flip($keys));
        if (count($keysData) != count($keys)) {
            $pk = implode($this->keySeparator, $keys);
            if (!isset($data[$pk])) {
                $this->addError($this->t('Request must contain keys: {keys} or complex key {pk}', array('keys' => implode(', ', $keys), 'pk' => $pk)));
                return null;
            }

            $keysData = array_combine($keys, explode($this->keySeparator, $data[$pk], count($keys)));
        }

        $query = $this->getActiveQuery();
        $query->andWhere($keysData);

        return $query->one();
    }

    public function setExtendMode($value)
    {
        return $this->_extendMode = $value;
    }

    public function getExtendMode()
    {
        return $this->_extendMode || $this->extraFields;
    }

    /**
     * @param $model \yii\db\ActiveRecord
     * @return array
     */
    public function getModelData($model)
    {
        $data = $this->getExtendMode()
            ? $model->toArray($this->getAttributes(), $this->getExtraFields())
            : $model->getAttributes($this->attributeNames, $this->exceptAttributes);

        if (count($keys = $model::primaryKey()) > 1) {
            $data[implode($this->keySeparator, $keys)] = implode($this->keySeparator, $model->getPrimaryKey(true));
        }

        return $data;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getActiveQuery()
    {
        if ($this->_activeQuery === null) {
            $modelClass = $this->getModelClass();

            $this->_activeQuery = $modelClass::find();
            if ($this->query) {
                \Yii::configure($this->_activeQuery, $this->query);
            }
        }

        return $this->_activeQuery;
    }

    public function addError($error)
    {
        $this->_errors[] = $error;
    }

    /**
     * @param $model \yii\db\ActiveRecord
     */
    public function addValidationErrors($model)
    {
        list($key) = array_values($model->getPrimaryKey(true));
        $this->_errors['validation'][$key] = $model->getErrors();
    }

    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * @param bool $autoFill fill attribute names if $attributeNames is empty
     * @return array
     */
    public function getAttributes($autoFill = false)
    {
        $attributes = !$autoFill && !$this->exceptAttributes || $this->attributeNames
            ? $this->attributeNames
            : $this->getModelInstance()->attributes();

        if ($this->exceptAttributes) {
            return array_diff($attributes, $this->exceptAttributes);
        }

        return $attributes;
    }

    public function getExtraFields()
    {
        return $this->extraFields;
    }

    /**
     * @param $params
     * @return array list of request parameters
     */
    public function setRequestParams($params)
    {
        return $this->_requestParams = array_merge($this->_requestParams, $params);
    }

    /**
     * @return array|string list of request parameters or request parameter for $param
     */
    public function getRequestParams($param = null)
    {
        return $param
            ? (isset($this->_requestParams[$param]) ? $this->_requestParams[$param] : null)
            : $this->_requestParams;
    }

    /**
     * @param $params
     * @return array list of response parameters
     */
    public function setResponseParams($params)
    {
        return $this->_responseParams = array_merge($this->_responseParams, $params);
    }

    /**
     * @return array|string list of response parameters or response parameter for $param
     */
    public function getResponseParams($param = null)
    {
        return $param ? $this->_responseParams[$param] : $this->_responseParams;
    }

    public function getRequestData($param = null)
    {
        if ($this->_requestData === null) {
            $this->_requestData = \Yii::$app->request->post() ?: \Yii::$app->request->get();
        }

        return $param === null
            ? $this->_requestData
            : (isset($this->_requestData[$this->getRequestParams($param)])
                ? $this->_requestData[$this->getRequestParams($param)]
                : null);
    }

    public function setRequestData($values)
    {
        $this->_requestData = $values;
    }

    public function getRequestModels()
    {
        if (is_null($data = $this->getRequestData('models'))) {
            // batch option is set to false
            $data = $this->getRequestData();
            $data = $data ? [$data] : null;
        }

        return $data;
    }

    public function getResponseData()
    {
        if ($this->_responseData === null) {
            $this->_responseData = $this->collectResponseData();
        }

        return $this->_responseData;
    }

    public function collectResponseData()
    {
        $responseData = [$this->getResponseParams('data') => $this->data];

        if ($errors = $this->getErrors()) {
            $responseData[$this->getResponseParams('errors')] = $errors;
        }

        return $responseData;
    }
}
