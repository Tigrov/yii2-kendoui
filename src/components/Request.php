<?php

namespace tigrov\kendoui\components;

use yii\base\BaseObject;

/**
 * Class Request
 *
 * @property array $params key => value request parameters names specified by DataSource.transport.parameterMap
 * (default: take, skip, page, pageSize, filter, sort, models, group, aggregate)
 * @property array $data data from request (default $_POST ?: $_GET)
 *
 * @property-read array $models
 */
class Request extends BaseObject
{
    private $_data;

    /**
     * @var array request params for result manipulation (limit, offset, filter, sort and etc.)
     * specified by DataSource.transport.parameterMap
     */
    private $_params = [
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
     * @param array $params
     * @return array list of request parameters
     */
    public function setParams($params)
    {
        return $this->_params = array_merge($this->_params, $params);
    }

    /**
     * @return array|string list of request parameters or request parameter for $param
     */
    public function getParams($param = null)
    {
        return $param ? $this->_params[$param] : $this->_params;
    }

    public function setData($values)
    {
        $this->_data = $values;
    }

    public function getData($param = null)
    {
        if ($this->_data === null) {
            $this->_data = \Yii::$app->request->post() ?: \Yii::$app->request->get();
        }

        return $param === null
            ? $this->_data
            : (isset($this->_data[$this->getParams($param)])
                ? $this->_data[$this->getParams($param)]
                : null);
    }

    public function getModels()
    {
        $data = $this->getData('models');
        if ($data === null) {
            // batch option is set to false
            $data = $this->getData();
            $data = $data ? [$data] : null;
        }

        return $data;
    }
}