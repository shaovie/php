<?php
/**
 * @Author shaowei
 * @Date   2015-09-21
 */

namespace src\common;

class Util
{
    // 获取客户端IP
    public static function getIp()
    {
        $ip = !empty($_SERVER['HTTP_VIA']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
        $ip = !empty($ip) ? $ip : $_SERVER['REMOTE_ADDR'];
        return $ip;
    }

    public static function getRandomStr($length)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; ++$i) {
            $str .= $chars[mt_rand(0, 61/*len($chars)-1*/)];
        }
        return $str;
    }

    public static function getMillSecond()
    {
        list($s1, $s2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }

    public static function emojiEncode($str)
    {
        $length = mb_strlen($str, 'utf-8');
        $strEncode = '';
        for ($i = 0; $i < $length; $i++) {
            $tmpStr = mb_substr($str, $i, 1, 'utf-8');
            if (strlen($tmpStr) >= 4) {
                $strEncode .= '[[EMJ:' . rawurlencode($tmpStr) . ']]';
            } else {
                $strEncode .= $tmpStr;
            }
        }
        return $strEncode;
    }

    public static function emojiDecode($strEncode)
    {
        $strDecode = preg_replace_callback("/\[\[EMJ:(.*?)\]\]/",
            function ($matches) { return rawurldecode($matches[1]); },
            $strEncode
        );
        return $strDecode;
    }

    public static function timeLimitFunction($ck, $expire, $func, $params)
    {
        $ret = Cache::get($ck);
        if (!empty($ret)) {
            return false;
        }
        if (call_user_func_array($func, $params)) {
            Cache::setex($ck, $expire, 'x');
            return true;
        }
        return false;
    }

    public static function arrayToXml($arr)
    {
        $xml = '<xml>';
        foreach ($arr as $key => $val) {
            $xml .= '<' . $key . '><![CDATA[' . $val . ']]></' . $key . '>';  
        }
        $xml .= '</xml>';
        return $xml; 
    }
}
