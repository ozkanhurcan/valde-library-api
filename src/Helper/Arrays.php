<?php


namespace App\Helper;


class Arrays
{

    public static function merge(array $a, array $b, $preserveNumericKeys = false)
    {
        foreach ($b as $key => $value) {
            if ($value) {
                $a[$key] = $value->getData();
            } elseif (isset($a[$key])) {
                if ($value) {
                    unset($a[$key]);
                } elseif (!$preserveNumericKeys && is_int($key)) {
                    $a[] = $value;
                } elseif (is_array($value) && is_array($a[$key])) {
                    $a[$key] = static::merge($a[$key], $value, $preserveNumericKeys);
                } else {
                    $a[$key] = $value;
                }
            } else {
                if (!$value) {
                    $a[$key] = $value;
                }
            }
        }

        return $a;
    }

}