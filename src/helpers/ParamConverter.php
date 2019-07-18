<?php
/**
 * @link https://github.com/Tigrov/yii2-kendoui
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

namespace tigrov\kendoui\helpers;

use yii\db\ActiveRecord;
use yii\db\Schema;

/**
 * Class ParamConverter
 *
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */
class ParamConverter
{
    const COMMON_OPERATORS = [
        'isnull' => 'IS NULL',
        'isnotnull' => 'IS NULL',
    ];

    /**
     * @var array query operators for filter by string fields
     */
    const STRING_OPERATORS = [
        'eq' => '=',
        'neq' => '!=',
        'doesnotcontain' => 'not like',
        'contains' => 'like',
        'startswith' => 'like',
        'endswith' => 'like',
        'isempty' => '=',
        'isnotempty' => '!=',
    ];

    /**
     * @var array query operators for filter by number fields
     */
    const NUMBER_OPERATORS = [
        'eq' => '=',
        'neq' => '!=',
        'gt' => '>',
        'gte' => '>=',
        'lt' => '<',
        'lte' => '<=',
    ];

    /**
     * @var array query functions for aggregate by fields
     */
    const AGGREGATE_FUNCTIONS = [
        'average' => 'AVG',
        'min' => 'MIN',
        'max' => 'MAX',
        'count' => 'COUNT',
        'sum' => 'SUM',
    ];

    /**
     * Convert aggregate query from Kendo UI to DB aggregate functions
     *
     * @param array $aggregates usually values of $_POST['aggregates']
     * @param ActiveRecord $model model for generation aggregate functions
     * @return array aggregate functions for Query::select()
     */
    public static function aggregate($aggregates, ActiveRecord $model)
    {
        if (!$aggregates || !is_array($aggregates)) {
            return [];
        }

        $db = $model->getDb();
        $attributes = $model->attributes();
        $aggregateFunctions = static::AGGREGATE_FUNCTIONS;

        $functions = [];
        foreach ($aggregates as $aggregate) {
            if (!empty($aggregate['aggregate']) && !empty($aggregate['field'])) {
                $aggregateKey = strtolower($aggregate['aggregate']);
                if (isset($aggregateFunctions[$aggregateKey]) && in_array($aggregate['field'], $attributes)) {
                    $funcName = $aggregateFunctions[$aggregateKey];
                    $functions[] = $funcName . '(' . $db->quoteColumnName($aggregate['field']) . ') '
                        . ' AS ' . $db->quoteColumnName($aggregateKey . '_' . $aggregate['field']);
                }
            }
        }

        return $functions;
    }

    public static function aggregateValues($fields)
    {
        $values = [];
        foreach ($fields as $field => $value) {
            list($aggregate, $attribute) = explode('_', $field, 2);
            $values[$attribute][$aggregate] = $value;
        }

        return $values;
    }

    /**
     * Convert sort query from Kendo UI to Yii2 orderBy columns
     *
     * @param array $sort usually values of $_POST['sort']
     * @param ActiveRecord $model model for generation orderBy columns
     * @return array columns for Query::orderBy()
     */
    public static function sort($sort, ActiveRecord $model)
    {
        if (!$sort || !is_array($sort)) {
            return [];
        }

        $attributes = $model->attributes();

        $columns = [];
        foreach($sort as $s) {
            if (!empty($s['field']) && in_array($s['field'], $attributes)) {
                $columns[$s['field']] = isset($s['dir']) && strtolower($s['dir']) == 'desc'
                    ? SORT_DESC
                    : SORT_ASC;
            }
        }

        return $columns;
    }

    /**
     * Convert filter query from Kendo UI to Yii2 where condition
     *
     * @param array $filter usually values of $_POST['filter']
     * @param ActiveRecord $model model for generation where condition
     * @return array|null where condition for Query::where()
     */
    public static function filter($filter, ActiveRecord $model)
    {
        if (!$filter || !is_array($filter)) {
            return null;
        }

        if (!empty($filter['filters']) && is_array($filter['filters'])) {
            return static::filterFilters($filter, $model);
        }

        $condition = null;
        $columns = $model::getTableSchema()->columns;
        if (!empty($filter['field']) && isset($columns[$filter['field']])
            && isset($filter['value']) && !empty($filter['operator'])
        ) {
            $attribute = $model::tableName() . '.' . $filter['field'];
            $filter['operator'] = strtolower($filter['operator']);
            $condition = static::filterCommon($attribute, $filter, $model)
                ?: static::filterNumber($attribute, $filter, $model)
                ?: static::filterString($attribute, $filter, $model);
        }

        return $condition;
    }

    protected static function filterFilters($filter, ActiveRecord $model)
    {
        $logic = isset($filter['logic']) && strtolower($filter['logic']) == 'or'
            ? 'or'
            : 'and';

        $where = [];
        foreach ($filter['filters'] as $flt) {
            if ($condition = static::filter($flt, $model)) {
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

    protected static function filterCommon($attribute, $filter, ActiveRecord $model)
    {
        $commonOperators = static::COMMON_OPERATORS;
        if (isset($commonOperators[$filter['operator']])) {
            switch ($filter['operator']) {
                case 'isnull':
                    return [$attribute => null];
                case 'isnotnull':
                    return ['not', [$attribute => null]];
            }
        }

        return null;
    }

    protected static function filterNumber($attribute, $filter, ActiveRecord $model)
    {
        $numberOperators = static::NUMBER_OPERATORS;
        if (isset($numberOperators[$filter['operator']])) {
            $operator = $numberOperators[$filter['operator']];

            $value = null;
            $type = $model::getTableSchema()->columns[$filter['field']]->type;
            if (in_array($type, [Schema::TYPE_INTEGER, Schema::TYPE_BIGINT, Schema::TYPE_TIMESTAMP, Schema::TYPE_DATE, Schema::TYPE_DATETIME, Schema::TYPE_TIME])) {
                $value = DataSourceHelper::convertDateToDb($value, $type);
            }

            if ($value === null) {
                if (is_numeric($filter['value'])) {
                    $value = (float)$filter['value'];
                } elseif (in_array($filter['value'], ['true', 'false'], true)) {
                    $operator = $filter['value'] === 'false'
                        ? $numberOperators['eq']
                        : $numberOperators['neq'];
                    $value = 0;
                }
            }

            if ($value !== null) {
                return [$operator, $attribute, $value];
            }
        }

        return null;
    }

    protected static function filterString($attribute, $filter, ActiveRecord $model)
    {
        $stringOperators = static::STRING_OPERATORS;
        if (isset($stringOperators[$filter['operator']])) {
            $operator = $stringOperators[$filter['operator']];
            $value = static::prepareStringValue($filter);
            if ($value !== null) {
                $attribute = 'LOWER(CAST(' . $model::getDb()->quoteColumnName($attribute) . ' AS text))';
                return [$operator, $attribute, $value, false];
            }
        }

        return null;
    }

    protected static function prepareStringValue($filter)
    {
        $value = strtolower($filter['value']);
        switch ($filter['operator']) {
            case 'contains':
            case 'doesnotcontain':
                return "%$value%";
            case 'startswith':
                return "$value%";
            case 'endswith':
                return "%$value";
            case 'isempty':
            case 'isnotempty':
                return '';
        }

        return $value;
    }
}