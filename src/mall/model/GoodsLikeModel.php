<?php
/**
 * @Author shaowei
 * @Date   2015-12-24
 */

namespace src\mall\model;

use \src\common\Cache;
use \src\common\Log;
use \src\common\DB;

class GoodsLikeModel
{
    public static function newOne($userId, $goodsId)
    {
        if (empty($userId)
            || empty($goodsId)) {
            return false;
        }

        $data = array(
            'goods_id'  => $goodsId,
            'user_id'   => $userId,
            'ctime'     => CURRENT_TIME,
        );
        $ret = DB::getDB('w')->insertOne('g_goods_like', $data);
        if ($ret === false) {
            return false;
        }
        $ck = Cache::CK_GOODS_HAD_LIKE . $goodsId . ':' . $userId;
        Cache::setex($ck, Cache::CK_GOODS_HAD_LIKE_EXPIRE, '1');
        return true;
    }

    // return true or false
    public static function hadLiked($userId, $goodsId)
    {
        $ck = Cache::CK_GOODS_HAD_LIKE . $goodsId . ':' . $userId;
        $ret = Cache::get($ck);
        if ($ret === false) {
            $ret = DB::getDB()->fetchCount(
                'g_goods_like',
                array('goods_id', 'user_id'), array($goodsId, $userId),
                array('and')
            );
            if ($ret !== false) {
                Cache::setex($ck, Cache::CK_GOODS_HAD_LIKE_EXPIRE, (string)$ret);
            }
        }
        return (int)$ret > 0;
    }
}

