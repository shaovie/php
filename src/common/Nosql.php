<?php
/**
 * @Author shaowei
 * @Date   2015-07-18
 */

namespace src\common;

class Nosql
{
    private static $nosql = false;

    //= define keys
    // format   name1:[name2:]
    // 缓存KEY的前缀已经在Redis中配置过了，这里就不需要加了
    const NK_MONITOR_LOG_EXPIRE      = 'monitor_log_expire:'; const NK_MONITOR_LOG_EXPIRE = 1800;

    //= for pay
    const NK_PAY_NOTIFY_DE_DUPLICATION  = 'pay_notify_de_duplication:';
    const NK_PAY_NOTIFY_DE_DUPLICATION_EXPIRE = 86400;

    const NK_WX_UNIFIED_PAY_UNSUBSCRIBE = 'wx_unified_pay_unsubscribe:';
    const NK_WX_UNIFIED_PAY_UNSUBSCRIBE_EXPIRE = 86400;
    
    //= public static methods
    //
    private static function getNosql()
    {
        if (self::$nosql == false) {
            self::$nosql = new Redis(REDIS_NOSQL_HOST, REDIS_NOSQL_PORT, NOSQL_PREFIX . ':');
        }
        return self::$nosql;
    }
    public static function get($key)
    {
        $ret = self::getNosql()->get($key);
        if ($ret === false) {
            return self::getNosql()->get($key);
        }
        return $ret;
    }
    public static function set($key, $v)
    {
        $ret = self::getNosql()->set($key, $v);
        if ($ret === false) {
            return self::getNosql()->set($key, $v);
        }
        return $ret;
    }
    public static function setex($key, $expire/*sec*/, $v)
    {
        $ret = self::getNosql()->setex($key, $expire, $v);
        if ($ret === false) {
            return self::getNosql()->setex($key, $expire, $v);
        }
        return $ret;
    }
    public static function expire($key, $expire/*sec*/)
    {
        $ret = self::getNosql()->expire($key, $expire);
        if ($ret === false) {
            return self::getNosql()->expire($key, $expire);
        }
        return $ret;
    }
    public static function setTimeout($key, $timeout/*sec*/)
    {
        $ret = self::getNosql()->setTimeout($key, $timeout);
        if ($ret === false) {
            return self::getNosql()->setTimeout($key, $timeout);
        }
        return $ret;
    }
    public static function del($key)
    {
        $ret = self::getNosql()->del($key);
        if ($ret === false) {
            return self::getNosql()->del($key);
        }
        return $ret;
    }
    public static function incr($key)
    {
        $ret = self::getNosql()->incr($key);
        if ($ret === false) {
            return self::getNosql()->incr($key);
        }
        return $ret;
    }
    public static function lpush($key, $v)
    {
        $ret = self::getNosql()->lpush($key, $v);
        if ($ret === false) {
            return self::getNosql()->lpush($key, $v);
        }
        return $ret;
    }
    public static function rpush($key, $v)
    {
        $ret = self::getNosql()->rpush($key, $v);
        if ($ret === false) {
            return self::getNosql()->rpush($key, $v);
        }
        return $ret;
    }
    public static function lpop($key)
    {
        $ret = self::getNosql()->lpop($key);
        if ($ret === false) {
            return self::getNosql()->lpop($key);
        }
        return $ret;
    }
    public static function lrange($key, $start, $end)
    {
        $ret = self::getNosql()->lrange($key, $start, $end);
        if ($ret === false) {
            return self::getNosql()->lrange($key, $start, $end);
        }
        return $ret;
    }
    public static function lsize($key)
    {
        $ret = self::getNosql()->lsize($key);
        if ($ret === false) {
            return self::getNosql()->lsize($key);
        }
        return $ret;
    }
    public static function ltrim($key, $start, $stop)
    {
        $ret = self::getNosql()->ltrim($key, $start, $stop);
        if ($ret === false) {
            return self::getNosql()->ltrim($key, $start, $stop);
        }
        return $ret;
    }
}
