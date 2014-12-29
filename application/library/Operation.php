<?php

/**
 * Created by PhpStorm.
 * User: jonson
 * Date: 14-2-16
 * Time: 下午2:01
 */
class Operation
{
    /**
     * @param $rows
     * @param string $id
     * @param string $pid
     * @param string $key
     * @return array
     * 无限分类
     */
    public static function generateTree(&$rows, $id = 'id', $pid = 'pid', $key = 'son')
    {
        $items = array();
        foreach ($rows as $row) $items[$row[$id]] = $row;
        foreach ($items as $item) $items[$item[$pid]][$key][$item[$id]] = & $items[$item[$id]];
        $t_array = isset($items[0][$key]) ? $items[0][$key] : array();
        return self::arrayReIndex($t_array, $key);
    }

    public static function arrayReIndex(& $arr, $key)
    {
        $arr = array_values($arr);
        foreach ($arr as $k => $value) {
            if (isset($value[$key])) {
                $value[$key] = self::arrayReIndex($value[$key], $key);
                $arr[$k][$key] = $value[$key];
            }
        }
        return $arr;
    }
} 