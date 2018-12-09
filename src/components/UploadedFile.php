<?php
/**
 * @link https://github.com/Tigrov/yii2-kendoui
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */

namespace tigrov\kendoui\components;

/**
 * Extended UploadedFile class.
 *
 * @author Sergei Tigrov <rrr-r@ya.ru>
 */
class UploadedFile extends \yii\web\UploadedFile
{
    public static function getInstancesByNames($names = null)
    {
        $ref = new \ReflectionClass(parent::class);
        $method = $ref->getMethod('loadFiles');
        $method->setAccessible(true);
        $files = $method->invoke(null);

        $results = [];
        foreach ($files as $key => $file) {
            if (!is_array($names) || in_array($key, $names)) {
                $results[] = new static($file);
            } else {
                foreach ($names as $name) {
                    if (strpos($key, "{$name}[") === 0) {
                        $results[] = new static($file);
                    }
                }
            }
        }

        return $results;
    }
}