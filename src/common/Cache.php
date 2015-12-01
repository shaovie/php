<?php
/**
 * @Author shaowei
 * @Date   2015-07-18
 */

namespace src\common;

class Cache
{
    private static $cache = false;

    //= define keys
    // format   name1:[name2:]
    // 缓存KEY的前缀已经在Redis中配置过了，这里就不需要加了

    //= for weixin
    const CK_WX_ACCESS_TOKEN         = 'wx_access_token:';        // expire probably 7200-300
    const CK_WX_JSAPI_TICKET         = 'wx_jsapi_ticket:';        // expire probably 7200-300
    const CK_SHORT_URL               = 'short_url:';              // forever
    const CK_WX_SCENE_QRCODE         = 'wx_scene_qrcode:';    const CK_WX_SCENE_QRCODE_EXPIRE = 1800;

    //= for baidu
    const CK_BAIDU_IP_TO_LOCATION    = 'baidu_ip2location:';     // forever
    const CK_BAIDU_LAT_LNG_TO_ADDR   = 'baidu_lat_lng_2_addr:';  // forever
    const CK_BAIDU_CITY_INFO         = 'baidu_city_info:';       // forever
    const CK_BAIDU_WX_GEOCONV        = 'baidu_wx_geoconv:';      // forever
    
    //= for user
    const CK_USER_ADDR_LIST          = 'user_addr_list:';        // forever

    //= public static methods
    //
    private static function getCache()
    {
        if (self::$cache == false) {
            self::$cache = new Redis(REDIS_CACHE_HOST, REDIS_CACHE_PORT, CACHE_PREFIX . ':');
        }
        return self::$cache;
    }
    public static function get($key)
    {
        $ret = self::getCache()->get($key);
        if ($ret === false) {
            return self::getCache()->get($key);
        }
        return $ret;
    }
    public static function set($key, $v)
    {
        $ret = self::getCache()->set($key, $v);
        if ($ret === false) {
            return self::getCache()->set($key, $v);
        }
        return $ret;
    }
    public static function setex($key, $expire/*sec*/, $v)
    {
        $ret = self::getCache()->setex($key, $expire, $v);
        if ($ret === false) {
            return self::getCache()->setex($key, $expire, $v);
        }
        return $ret;
    }
    public static function expire($key, $expire/*sec*/)
    {
        $ret = self::getCache()->expire($key, $expire);
        if ($ret === false) {
            return self::getCache()->expire($key, $expire);
        }
        return $ret;
    }
    public static function setTimeout($key, $timeout/*sec*/)
    {
        $ret = self::getCache()->setTimeout($key, $timeout);
        if ($ret === false) {
            return self::getCache()->setTimeout($key, $timeout);
        }
        return $ret;
    }
    public static function del($key)
    {
        $ret = self::getCache()->del($key);
        if ($ret === false) {
            return self::getCache()->del($key);
        }
        return $ret;
    }
    public static function incr($key)
    {
        $ret = self::getCache()->incr($key);
        if ($ret === false) {
            return self::getCache()->incr($key);
        }
        return $ret;
    }
    public static function lpush($key, $v)
    {
        $ret = self::getCache()->lpush($key, $v);
        if ($ret === false) {
            return self::getCache()->lpush($key, $v);
        }
        return $ret;
    }
    public static function rpush($key, $v)
    {
        $ret = self::getCache()->rpush($key, $v);
        if ($ret === false) {
            return self::getCache()->rpush($key, $v);
        }
        return $ret;
    }
    public static function lpop($key)
    {
        $ret = self::getCache()->lpop($key);
        if ($ret === false) {
            return self::getCache()->lpop($key);
        }
        return $ret;
    }
    public static function lrange($key, $start, $end)
    {
        $ret = self::getCache()->lrange($key, $start, $end);
        if ($ret === false) {
            return self::getCache()->lrange($key, $start, $end);
        }
        return $ret;
    }
    public static function lsize($key)
    {
        $ret = self::getCache()->lsize($key);
        if ($ret === false) {
            return self::getCache()->lsize($key);
        }
        return $ret;
    }
    public static function ltrim($key, $start, $stop)
    {
        $ret = self::getCache()->ltrim($key, $start, $stop);
        if ($ret === false) {
            return self::getCache()->ltrim($key, $start, $stop);
        }
        return $ret;
    }
}
