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

    public static function isPasswd($v)
    {
        $len = strlen($v);
        if ($len < 6 || $len > 18) {
            return false;
        }
        return preg_match('/^[\w~!@#$%^&*()\-_=+,.:;]{6,18}$/', $v);
    }
}

