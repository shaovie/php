<?php
/**
 * @Author shaowei
 * @Date   2015-09-21
 */

namespace src\common;

class Check
{
    public static function isPhone($v)
    {
        if (empty($v)) {
            return false;
        }
        return preg_match('/^1[3-8]\d{9}$/', $v);
    }

    public static function isSkuAttr($v)
    {
        return strlen($v) <= 36;
    }

    public static function isSkuValue($v)
    {
        return strlen($v) <= 60;
    }

    public static function isPasswd($v)
    {
        $len = strlen($v);
        if ($len < 6 || $len > 18) {
            return false;
        }
        return preg_match('/^[\w~!@#$%^&*()\-_=+,.:;]{6,18}$/', $v);
    }

    // 只允许中文或英文+数字+下划线
    public static function isName($str)
    {
        if (empty($str)) {
            return false;
        }
        return preg_match('/^[\x{4e00}-\x{9fa5}A-Za-z0-9_]+$/u', $str);
    }
}

