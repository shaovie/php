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
    const CK_WX_TMP_SCENE_QRCODE     = 'wx_tmp_scene_qrcode:'; const CK_WX_SCENE_QRCODE_EXPIRE = 3600;

    //= for baidu
    const CK_BAIDU_IP_TO_LOCATION    = 'baidu_ip2location:';     // forever
    const CK_BAIDU_LAT_LNG_TO_ADDR   = 'baidu_lat_lng_2_addr:';  // forever
    const CK_BAIDU_CITY_INFO         = 'baidu_city_info:';       // forever
    const CK_BAIDU_WX_GEOCONV        = 'baidu_wx_geoconv:';      // forever
    
    //= for user
    const CK_USER_INFO_FOR_PHONE     = 'user_info_for_phone:';
    const CK_USER_INFO_FOR_ID        = 'user_info_for_id:';
    const CK_WX_USER_INFO            = 'wx_user_info:';
    const CK_WX_USER_INFO_FOR_UID    = 'wx_user_info_for_uid:';
    const CK_USER_DETAIL_INFO        = 'user_detail_info:';
    const CK_USER_ADDR_LIST          = 'user_addr_list:';        // forever
    const CK_ORDER_INFO              = 'order_info:'; const CK_ORDER_INFO_EXPIRE = 7200;
    const CK_CART_LIST               = 'cart_list:'; const CK_CART_LIST_EXPIRE = 86400;
    const CK_GOODS_HAD_LIKE          = 'goods_had_like:'; const CK_GOODS_HAD_LIKE_EXPIRE = 86400;
    const CK_GOODS_COMMENT_HAD_LIKE  = 'goods_comment_had_like:'; const CK_GOODS_COMMENT_HAD_LIKE_EXPIRE = 86400;
    const CK_GOODS_HAD_COMMENT       = 'goods_had_comment:'; const CK_GOODS_HAD_COMMENT_EXPIRE = 86400;
    const CK_GOODS_INFO              = 'goods_info:'; const CK_GOODS_INFO_EXPIRE = 86400;
    const CK_GOODS_SKU               = 'goods_sku:'; const CK_GOODS_SKU_EXPIRE = 86400;

    const CK_MONITOR_LOG             = 'monitor_log:'; const CK_MONITOR_LOG_EXPIRE = 60;

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
    public static function mGet($key)
    {
        $ret = self::getCache()->mGet($key);
        if ($ret === false) {
            return self::getCache()->mGet($key);
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
    public static function setEx($key, $expire/*sec*/, $v)
    {
        $ret = self::getCache()->setEx($key, $expire, $v);
        if ($ret === false) {
            return self::getCache()->setEx($key, $expire, $v);
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
    public static function lPush($key, $v)
    {
        $ret = self::getCache()->lPush($key, $v);
        if ($ret === false) {
            return self::getCache()->lPush($key, $v);
        }
        return $ret;
    }
    public static function rPush($key, $v)
    {
        $ret = self::getCache()->rPush($key, $v);
        if ($ret === false) {
            return self::getCache()->rPush($key, $v);
        }
        return $ret;
    }
    public static function lPop($key)
    {
        $ret = self::getCache()->lPop($key);
        if ($ret === false) {
            return self::getCache()->lPop($key);
        }
        return $ret;
    }
    public static function lRange($key, $start, $end)
    {
        $ret = self::getCache()->lRange($key, $start, $end);
        if ($ret === false) {
            return self::getCache()->lRange($key, $start, $end);
        }
        return $ret;
    }
    public static function lSize($key)
    {
        $ret = self::getCache()->lSize($key);
        if ($ret === false) {
            return self::getCache()->lSize($key);
        }
        return $ret;
    }
    public static function lTrim($key, $start, $stop)
    {
        $ret = self::getCache()->lTrim($key, $start, $stop);
        if ($ret === false) {
            return self::getCache()->lTrim($key, $start, $stop);
        }
        return $ret;
    }
}
