<?php

namespace tigrov\kendoui\actions;

use tigrov\kendoui\helpers\ParamConverter;

class Read extends Action {
    /**
     * @var int maximum limit of rows
     */
    public $maxLimit = 1000;

    protected $total = 0;
    protected $aggregates;

    public function process()
    {
        $kendoData = $this->getKendoData();

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

        $rows = $kendoData->getActiveQuery()->asArray()->all();
        $this->data = $kendoData->getExtendMode()
            ? $kendoData->toModelArray($rows)
            : $kendoData->filterAttributes($rows);
    }

    public function getResult()
    {
        $kendoData = $this->getKendoData();
        $response = $kendoData->getResponse();
        $result = $response->getResult();
        $result[$response->getParams('total')] = $this->total;

        if ($this->aggregates) {
            $result[$response->getParams('aggregates')] = $this->aggregates;
        }

        return $result;
    }

    private function _calcAggregates()
    {
        $kendoData = $this->getKendoData();
        $db = $kendoData->getModelInstance()->getDb();
        $functions = ['COUNT(*) AS ' . $db->quoteColumnName('total')];
        if ($aggregates = ParamConverter::aggregate($kendoData->getRequest()->getData('aggregates'), $kendoData->getModelInstance())) {
            $functions = array_merge($functions, $aggregates);
        }

        $row = $this->_getAggregatesRow($functions);

        $total = $row['total'];
        unset($row['total']);

        return array_merge(['total' => $total], ParamConverter::aggregateValues($row));
    }

    private function _getAggregatesRow($functions)
    {
        $query = $this->getKendoData()->getActiveQuery();
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
        $kendoData = $this->getKendoData();
        if ($take = (int)$kendoData->getRequest()->getData('take')) {
            $take = $take < 1 || $take > $this->maxLimit ? $this->maxLimit : $take;
            $kendoData->getActiveQuery()->limit($take);
        }
    }

    private function _querySkip()
    {
        $kendoData = $this->getKendoData();
        if (($skip = (int)$kendoData->getRequest()->getData('skip')) > 0) {
            $kendoData->getActiveQuery()->offset($skip);
        }
    }

    private function _queryFilter()
    {
        $kendoData = $this->getKendoData();
        if ($condition = ParamConverter::filter($kendoData->getRequest()->getData('filter'), $kendoData->getModelInstance())) {
            $kendoData->getActiveQuery()->andWhere($condition);
        }
    }

    private function _querySort()
    {
        $kendoData = $this->getKendoData();
        if ($columns = ParamConverter::sort($kendoData->getRequest()->getData('sort'), $kendoData->getModelInstance())) {
            $kendoData->getActiveQuery()->addOrderBy($columns);
        }
    }
} 