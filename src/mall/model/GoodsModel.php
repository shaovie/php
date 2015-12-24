<?php
/**
 * @Author shaowei
 * @Date   2015-12-24
 */

namespace src\mall\model;

use \src\common\Cache;
use \src\common\Util;
use \src\common\Log;

class GoodsModel
{
    const GOODS_ST_INVALID         = 0;  // 无效
    const GOODS_ST_VALID           = 1;  // 无效
    const GOODS_ST_UP              = 2;  // 上架-展示在商城中
    const GOODS_ST_DOWN_VALID      = 3;  // 下架-有效
    const GOODS_ST_DOWN_INVALID    = 4;  // 下架-无效

    // 商品(外部判断状态)
    public static function findGoodsById($goodsId)
    {
        if (empty($goodsId)) {
            return array();
        }
        $ck = Cache::CK_GOODS_INFO . $goodsId; // TODO 商品被修改时一定要记得刷新缓存（比如供应商后台）
        $ret = Cache::get($ck);
        if ($ret !== false) {
            $ret = json_decode($ret, true);
        } else {
            $ret = DB::getDB()->fetchOne(
                'g_goods',
                '*',
                array('goods_id'), array($goodsId),
            );
            if ($ret !== false) {
                Cache::setex($ck, Cache::CK_GOODS_INFO_EXPIRE, json_encode($ret));
            }
        }
        return $ret === false ? array() : $ret;
    }

    public static function getSomeGoodsByCategory(
        $goodsId,
        $categoryId,
        $nextId,
        $orderBy,
        $orderType,
        $size
    ) {
        if (empty($goodsId) || empty($categoryId) || $size <= 0) {
            return array();
        }
        $nextId = (int)$nextId;
        if ($nextId > 0) {
            $ret = DB::getDB()->fetchSome(
                'g_goods',
                '*',
                array('goods_id', 'category_id', 'state', 'id<'),
                array($goodsId, $categoryId, self::GOODS_ST_UP, $nextId),
                array('and', 'and', 'and'),
                array($orderBy), array($orderType),
                array($size)
            );
        } else {
            $ret = DB::getDB()->fetchSome(
                'g_goods',
                '*',
                array('goods_id', 'category_id', 'state'),
                array($goodsId, $categoryId, self::GOODS_ST_UP),
                array('and', 'and'),
                array($orderBy), array($orderType),
                array($size)
            );
        }
        return empty($ret) ? array() : $ret;
    }
}

