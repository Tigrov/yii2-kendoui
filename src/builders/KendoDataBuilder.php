<?php
/**
 * @link https://github.com/Tigrov/yii2-kendoui
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

namespace tigrov\kendoui\builders;

use yii\base\InvalidConfigException;
use tigrov\kendoui\KendoData;

/**
 * Class KendoDataBuilder
 *
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */
class KendoDataBuilder
{
    const CLASS_NAME = '\tigrov\kendoui\KendoData';

    /**
     * @param array|string|KendoData $config
     * @return KendoData
     * @throws InvalidConfigException
     */
    public static function build($config)
    {
        $className = static::CLASS_NAME;
        if ($config instanceof $className) {
            return $config;
        } elseif (is_array($config)) {
            if (!isset($config['class'])) {
                $config['class'] = $className;
            }
        } elseif (is_string($config)) {
            $config = [
                'class' => $className,
                'model' => ['class' => $config],
            ];
        } else {
            throw new InvalidConfigException('KendoData configuration must be array, string, or instance of ' . static::CLASS_NAME .  '.');
        }

        return \Yii::createObject($config);
    }
}