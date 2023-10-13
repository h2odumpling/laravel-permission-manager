<?php

namespace H2o\PermissionManager\Helper;


class Helper
{
    public static function array_get(array &$array, string $key, callable $callback)
    {
        if (!array_key_exists($key, $array)) {
            $array[$key] = $callback();
        }
        return $array[$key];
    }

    public static function str_path_match(string $rule, string $subject, string $delimiter = '.'): bool
    {
        $units = explode($delimiter, $rule);
        $j = 0;
        $k = -1;
        foreach (explode($delimiter, $subject) as $i => $item) {
            if (empty($units[$j]) && $k === -1) return false;

            $unit = $units[$j];

            if ($unit === '**') $k = $j;

            if ($item === $unit || $unit === '*' || $unit === '**') {
                $j++;
            } else {
                if ($k === -1) return false;
                $j = $k + 1;
            }
        }
        return $j === count($units);
    }

    public static function scope_cmp($a, $b)
    {
        if ($a === $b) {
            return 0;
        }
        if ($a[0] === '_' && $b[0] !== '_') {
            return -1;
        }
        if ($b[0] === '_' && $a[0] !== '_') {
            return 1;
        }
        return $a < $b ? -1 : 1;
    }

    public static function scope_exists($scopes, $needle)
    {
        if ($needle === '*') {
            return true;
        }

        return !empty(array_uintersect($needle, $scopes, function ($a, $b) {
            if ($a1 = $a[0] === '@') {
                $a = substr($a, 1);
            }
            if ($b1 = $b[0] === '@') {
                $b = substr($b, 1);
            }
            if ($a1 === $b1) {
                return $a === $b ? 0 : ($a < $b ? -1 : 1);
            }
            if ($a1) {
                return strpos($b, $a) === 0 ? 0 : ($a < $b ? -1 : 1);
            }
            return strpos($a, $b) === 0 ? 0 : ($a < $b ? -1 : 1);
        }));
    }
}
