<?php
/**
 * @Author shaowei
 * @Date   2015-09-17
 */

namespace src\common;

class Session
{
    public static $cookie = array(
        'pre' => COOKIE_PREFIX . '_',
        'path'=> '/',
        'domain' => COOKIE_DOMAIN,
        'expire' => 2592000
    );

    public static function getSid($key = 'user')
    {
        $key = self::$cookie['pre'] . $key;
        if (!empty($_COOKIE[$key])) {
            return $_COOKIE[$key];
        }
        $r = (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '')
            . Util::getIP() . CURRENT_TIME;
        $value = md5($r);
        setcookie(
            $key,
            $value,
            CURRENT_TIME + self::$cookie['expire'],
            self::$cookie['path'],
            self::$cookie['domain']
        );
        return $value;
    }

    public static function setUserSession(
        $userId,
        $openid
    ) {
        $data['userId'] = $userId;
        $data['openid'] = $openid;
        $data['userAgent'] = isset($_SERVER['HTTP_USER_AGENT']) ?
            $_SERVER['HTTP_USER_AGENT'] : '';
        $key = self::getSid();
        Nosql::set(Nosql::NK_USER_SESSOIN . $key, json_encode($data));
    }
}

