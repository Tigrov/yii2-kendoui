<?php

namespace tigrov\kendoui\actions;

class Read extends Action {
    const DEFAULT_FILTER_LOGIC = 'and';

    protected $total = 0;
    protected $aggregates;

    /**
     * @var array query operators for filter by string fields
     */
    private $_stringOperators = [
        'eq' => 'like',
        'neq' => 'not like',
        'doesnotcontain' => 'not like',
        'contains' => 'like',
        'startswith' => 'like',
        'endswith' => 'like',
    ];

    /**
     * @var array query operators for filter by number fields
     */
    private $_numberOperators = [
        'eq' => '=',
        'gt' => '>',
        'gte' => '>=',
        'lt' => '<',
        'lte' => '<=',
        'neq' => '!=',
    ];

    /**
     * @var array query functions for aggregate by fields
     */
    private $_aggregateFunctions = [
        'average' => 'AVG',
        'min' => 'MIN',
        'max' => 'MAX',
        'count' => 'COUNT',
        'sum' => 'SUM',
    ];

    /**
     * @var int maximum limit of rows
     */
    public $maxLimit = 1000;

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->queryData();

        return parent::run();
    }

    public function queryData()
    {
        $this->_queryFilter();

        $aggregates = $this->_calcAggregates();
        $this->total = $aggregates['total'];
        unset($aggregates['total']);
        if ($aggregates) {
            $this->aggregates = $aggregates;
        }

        $this->_queryTake();
        $this->_querySkip();
        $this->_querySort();

        $rows = $this->getActiveQuery()->asArray()->all();
        $this->data = $this->getExtendMode()
            ? $this->toModelArray($rows)
            : $this->filterAttributes($rows);
    }

    public function toModelArray($rows)
    {
        $attributes = $this->getAttributes();
        $extraFields = $this->getExtraFields();

        $modelClass = $this->getModelClass();
        $keys = $modelClass::primaryKey();
        $keysCount = count($keys);
        $pk = implode($this->keySeparator, $keys);

        $data = [];
        foreach ($rows as $i => $row) {
            $model = $this->getModelInstance(true);
            $model->setAttributes($row, false);
            $data[$i] = $model->toArray($attributes, $extraFields);
            if ($keysCount > 1) {
                $data[$i][$pk] = implode($this->keySeparator, $model->getPrimaryKey(true));
            }
        }

        return $data;
    }

    public function filterAttributes($rows)
    {
        $attributes = $this->getAttributes();
        $modelClass = $this->getModelClass();
        $keys = $modelClass::primaryKey();

        $data = [];
        if (!$attributes) {
            $data = $rows;
        } else {
            $attributesFlip = array_flip($attributes);
            foreach ($rows as $i => $row) {
                $data[$i] = array_intersect_key($row, $attributesFlip);
            }
        }

        if (count($keys) > 1) {
            $pk = implode($this->keySeparator, $keys);
            $keysFlip = array_flip($keys);
            foreach ($rows as $i => $row) {
                $data[$i][$pk] = implode($this->keySeparator, array_intersect_key($row, $keysFlip));
            }
        }

        return $data;
    }

    public function collectResponseData()
    {
        $responseData = parent::collectResponseData();
        $responseData[$this->getResponseParams('total')] = $this->total;

        if ($this->aggregates) {
            $result[$this->getResponseParams('aggregates')] = $this->aggregates;
        }

        return $responseData;
    }

    private function _calcAggregates()
    {
        $functions = ['COUNT(*) AS `total`'];
        if (!is_null($aggregates = $this->getRequestData('aggregates')) && is_array($aggregates)) {
            $db = $this->getModelInstance()->getDb();
            foreach ($aggregates as $aggregate) {
                if (!empty($aggregate['aggregate']) && isset($this->_aggregateFunctions[$aggregate['aggregate']])
                    && !empty($aggregate['field']) && in_array($aggregate['field'], $this->getModelInstance()->attributes())
                ) {
                    $funcName = $this->_aggregateFunctions[$aggregate['aggregate']];
                    $functions[] = $funcName . '(' . $db->quoteColumnName($aggregate['field']) . ') '
                        . ' AS ' . $db->quoteColumnName($aggregate['aggregate'] . '_' . $aggregate['field']);
                }
            }
        }

        $row = $this->_getAggregatesRow($functions);

        $total = $row['total'];
        unset($row['total']);

        return array_merge(['total' => $total], $this->_getAggregatesValues($row));
    }

    private function _getAggregatesRow($functions)
    {
        $query = $this->getActiveQuery();
        $select = $query->select;
        $limit = $query->limit;
        $offset = $query->offset;
        $orderBy = $query->orderBy;

        $query->select = $functions;
        $query->limit = null;
        $query->offset = null;
        $query->orderBy = null;

        $command = $query->createCommand();

        $query->select = $select;
        $query->limit = $limit;
        $query->offset = $offset;
        $query->orderBy = $orderBy;

        return $command->queryOne();
    }

    private function _getAggregatesValues($row)
    {
        $values = [];
        foreach ($row as $field => $value) {
            list($aggregate, $attribute) = explode('_', $field, 2);
            $values[$attribute][$aggregate] = $value;
        }

        return $values;
    }

    private function _queryTake()
    {
        if ($take = (int)$this->getRequestData('take')) {
            $take = $take < 1 || $take > $this->maxLimit ? $this->maxLimit : $take;
            $this->getActiveQuery()->limit($take);
        }
    }

    private function _querySkip()
    {
        if ($skip = (int)$this->getRequestData('skip')) {
            if ($skip > 0) {
                $this->getActiveQuery()->offset($skip);
            }
        }
    }

    private function _queryFilter()
    {
        if (!is_null($filter = $this->getRequestData('filter'))) {
            if ($condition = $this->_filter($filter)) {
                $this->getActiveQuery()->andWhere($condition);
            }
        }
    }

    private function _querySort()
    {
        if (!is_null($sort = $this->getRequestData('sort')) && is_array($sort)) {
            foreach($sort as $s) {
                if (!empty($s['field']) && in_array($s['field'], $this->getModelInstance()->attributes())
                    && !empty($s['dir']) && in_array($s['dir'], ['asc', 'desc'], true)
                ) {
                    $this->getActiveQuery()->addOrderBy([$s['field'] => $s['dir'] == 'asc' ? SORT_ASC : SORT_DESC]);
                }
            }
        }
    }

    private function _filter($filter)
    {
        if (!empty($filter['filters']) && is_array($filter['filters'])) {
            $logic = in_array($filter['logic'], ['or', 'and'], true) ? $filter['logic'] : self::DEFAULT_FILTER_LOGIC;

            $where = [];
            foreach ($filter['filters'] as $flt) {
                if ($condition = $this->_filter($flt)) {
                    $where[] = $condition;
                }
            }

            if (count($where) > 1) {
                array_unshift($where, $logic);
            } else {
                $where = $where ? $where[0] : null;
            }

            return $where;
        }

        if (!empty($filter['field']) && in_array($filter['field'], $this->getModelInstance()->attributes())
                && isset($filter['value']) && !empty($filter['operator'])
        ) {
            $db = $this->getModelInstance()->getDb();
            $attribute = $filter['field'];
            $value = $operator = null;

            if (isset($this->_numberOperators[$filter['operator']])) {
                $operator = $this->_numberOperators[$filter['operator']];
                $value = static::parseDate($filter['value']);
                if ($value) {
                    $attribute = 'DATE(FROM_UNIXTIME(' . $db->quoteColumnName($attribute) . '))';
                } elseif (is_numeric($filter['value'])) {
                    $value = (float)$filter['value'];
                } elseif (in_array($filter['value'], ['true', 'false'])) {
                    $operator = $filter['value'] == 'false'
                        ? $this->_numberOperators['eq']
                        : $this->_numberOperators['neq'];
                    $value = 0;
                }
            }

            if ($value === null && isset($this->_stringOperators[$filter['operator']])) {
                $operator = $this->_stringOperators[$filter['operator']];
                $value = $filter['value'];
                if ($filter['operator'] == 'contains' || $filter['operator'] == 'doesnotcontain') {
                    $value = "%$value%";
                } elseif ($filter['operator'] == 'startswith') {
                    $value = "$value%";
                } elseif ($filter['operator'] == 'endswith') {
                    $value = "%$value";
                }
            }

            return $value !== null ? [$operator, $attribute, $value] : null;
        }

        return null;
    }

    public static function parseDate($value)
    {
        $result = date_parse($value);
        return $result["error_count"] < 1 && checkdate($result['month'], $result['day'], $result['year'])
            ? $result['year']
                    . '-' . str_pad($result['month'], 2, '0', STR_PAD_LEFT)
                    . '-' . str_pad($result['day'], 2, '0', STR_PAD_LEFT)
            : null;
    }
} 