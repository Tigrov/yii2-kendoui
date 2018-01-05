<?php

namespace tigrov\kendoui\components;

use yii\base\BaseObject;
use yii\helpers\ArrayHelper;

/**
 * Class Response
 *
 * @property string $format @see \yii\web\Response::$format (default \yii\web\Response::FORMAT_JSON)
 * @property array $params key => value response parameters names specified by DataSource.schema
 * (default: data, total, errors, groups, aggregates)
 * @property array $data data for response
 * @property-read array $errors list of errors
 */
class Response extends BaseObject
{
    public $format = \yii\web\Response::FORMAT_JSON;

    public $data = [];

    private $_errors = [];

    /**
     * @var array response params for result (limit, offset, filter, sort and etc.)
     * specified by DataSource.schema
     */
    private $_params = [
        'data' => 'data',
        'total' => 'total',
        'errors' => 'errors',
        'groups' => 'groups',
        'aggregates' => 'aggregates',
    ];

    /**
     * @param array $params
     * @return array list of response parameters
     */
    public function setParams($params)
    {
        return $this->_params = array_merge($this->_params, $params);
    }

    /**
     * @return array|string list of response parameters or response parameter for $param
     */
    public function getParams($param = null)
    {
        return $param ? $this->_params[$param] : $this->_params;
    }

    public function addError($message, $params = [])
    {
        $this->_errors[] = $params
            ? strtr($message, array_map(function($v){return'{'.$v.'}';}, array_keys($params)), $params)
            : $message;
    }

    public function addValidationErrors($errors)
    {
        if (isset($this->_errors['validation'])) {
            $this->_errors['validation'] = ArrayHelper::merge($this->_errors['validation'], $errors);
        } else {
            $this->_errors['validation'] = $errors;
        }
    }

    public function getErrors()
    {
        return $this->_errors;
    }

    public function addData($item)
    {
        $this->data[] = $item;
    }

    public function getResult()
    {
        $result = [$this->getParams('data') => $this->data];

        if ($errors = $this->getErrors()) {
            $result[$this->getParams('errors')] = $errors;
        }

        return $result;
    }
}