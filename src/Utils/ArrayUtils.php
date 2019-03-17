<?php declare(strict_types=1);

namespace GetNoticed\ComposerPatches\Utils;

class ArrayUtils
{
    /**
     * @param array  $array
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed|null
     */
    public static function get(array $array, string $key, $default = null)
    {
        return array_key_exists($key, $array) ? $array[$key] : $default;
    }
}
