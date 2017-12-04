<?php
namespace tigrov\kendoui\builders;

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
     * Also $config might contain list of action types ['actions' => ['create', 'read', 'update', 'delete']]
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
            return static::mergeConfig(['kendoData' => $config, 'actions' => static::actions()]);
        }

        if (empty($config['actions'])) {
            $config['actions'] = static::actions();
        } else {
            $config['actions'] = static::toAssociative($config['actions']);
        }

        if (empty($config['kendoData'])) {
            $config['kendoData'] = ArrayHelper::filter($config, ['actions']);
        }

        return static::mergeConfig($config);
    }

    public static function actions()
    {
        return [
            'create' => ['class' => '\tigrov\kendoui\actions\Create'],
            'read' => ['class' => '\tigrov\kendoui\actions\Read'],
            'update' => ['class' => '\tigrov\kendoui\actions\Update'],
            'delete' => ['class' => '\tigrov\kendoui\actions\Delete']
        ];
    }

    public static function mergeConfig($config)
    {
        $baseActions = static::actions();
        $prefix = static::prefix($config['kendoData']);

        $list = [];
        foreach ($config['actions'] as $id => $actionConfig) {
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

    public static function prefix($config)
    {
        $className = is_string($config)
            ? $config
            : (is_string($config['model'])
                ? $config['model']
                : $config['model']['class']);

        $shortName = (new \ReflectionClass($className))->getShortName();
        return Inflector::camel2id($shortName) . '-';
    }

    public static function toAssociative($actions)
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