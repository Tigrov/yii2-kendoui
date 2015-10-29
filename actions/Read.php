<?php

namespace tigrov\kendoui\actions;

use tigrov\kendoui\helpers\ParamConverter;

class Read extends Action {
    protected $total = 0;
    protected $aggregates;

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
            $model::populateRecord($model, $row);
            $model->afterFind();
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
        $db = $this->getModelInstance()->getDb();
        $functions = ['COUNT(*) AS ' . $db->quoteColumnName('total')];
        if ($aggregates = ParamConverter::aggregate($this->getRequestData('aggregates'), $this->getModelInstance())) {
            $functions = array_merge($functions, $aggregates);
        }

        $row = $this->_getAggregatesRow($functions);

        $total = $row['total'];
        unset($row['total']);

        return array_merge(['total' => $total], ParamConverter::aggregateValues($row));
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

    private function _queryTake()
    {
        if ($take = (int)$this->getRequestData('take')) {
            $take = $take < 1 || $take > $this->maxLimit ? $this->maxLimit : $take;
            $this->getActiveQuery()->limit($take);
        }
    }

    private function _querySkip()
    {
        if (($skip = (int)$this->getRequestData('skip')) > 0) {
            $this->getActiveQuery()->offset($skip);
        }
    }

    private function _queryFilter()
    {
        if ($condition = ParamConverter::filter($this->getRequestData('filter'), $this->getModelInstance())) {
            $this->getActiveQuery()->andWhere($condition);
        }
    }

    private function _querySort()
    {
        if ($columns = ParamConverter::sort($this->getRequestData('sort'), $this->getModelInstance())) {
            $this->getActiveQuery()->addOrderBy($columns);
        }
    }
} 