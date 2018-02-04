<?php
namespace tigrov\kendoui\builders;

use tigrov\kendoui\helpers\DataSourceHelper;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

class ActionsBuilder
{
    /**
     * Create actions list for Controller::actions()
     *
     * @param $config array|string Configuration for creating action list.
     * If $config is string then $config is using as modelClass.
     * If $config is array then it must contain settings for each action.
     * Also $config might contain list of action types ['actions' => ['create', 'read', 'update', 'destroy']]
     * and individual config for each action ['actions' => ['create' => ['model' => 'ClassName'], 'read']],
     * in the last example will have created list with two action types 'create' and 'read'.
     * Id for each action will have generated from "model class name" as prefix and type name as suffix "class-name-create".
     * Id can be specified as individual action setting ['actions' => ['create' => ['id' => 'model-create']]]
     * or ['actions' => ['create' => 'model-create']]
     *
     * @return array List of actions [id => settings]
     */
    public static function build($config)
    {
        if (is_string($config)) {
            $config = ['kendoData' => ['model' => ['class' => $config]], 'actions' => DataSourceHelper::actions()];
        } else {
            if (empty($config['kendoData'])) {
                $config['kendoData'] = array_diff_key($config, ['actions' => '']);
                $config = ArrayHelper::filter($config, ['kendoData', 'actions']);
            }
            if (is_string($config['kendoData'])) {
                $config['kendoData']['model']['class'] = $config['kendoData'];
            } elseif (is_string($config['kendoData']['model'])) {
                $config['kendoData']['model']['class'] = $config['kendoData']['model'];
            }
            if (empty($config['actions'])) {
                $config['actions'] = DataSourceHelper::actions();
            } else {
                $config['actions'] = static::toAssociative($config['actions']);
            }
        }

        return static::mergeConfig($config);
    }

    private static function mergeConfig($config)
    {
        $baseActions = DataSourceHelper::actions();
        $actions = $config['actions'];
        unset($config['actions']);
        $prefix = static::prefix($config['kendoData']);

        $list = [];
        foreach ($actions as $id => $actionConfig) {
            if (is_array($actionConfig)) {
                $actionPrefix = !empty($actionConfig['kendoData']) ? static::prefix($actionConfig['kendoData']) : $prefix;
                $actionId = !empty($actionConfig['id']) ? $actionConfig['id'] : $actionPrefix.$id;
                unset($actionConfig['id']);
            } else {
                $actionId = $actionConfig;
                $actionConfig = [];
            }

            $list[$actionId] = ArrayHelper::merge($baseActions[$id], $config, $actionConfig);
        }

        return $list;
    }

    private static function prefix($config)
    {
        $shortName = (new \ReflectionClass($config['model']['class']))->getShortName();
        return Inflector::camel2id($shortName) . '-';
    }

    private static function toAssociative($actions)
    {
        $list = [];
        foreach ($actions as $id => $config) {
            if (is_numeric($id)) {
                $list[$config] = [];
            } else {
                $list[$id] = $config;
            }
        }

        return $list;
    }
} 