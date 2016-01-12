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
    public static function findGoodsById($goodsId, $fromDb = 'w')
    {
        if (empty($goodsId)) {
            return array();
        }
        $ck = Cache::CK_GOODS_INFO . $goodsId; // TODO 商品被修改时一定要记得刷新缓存（比如供应商后台）
        $ret = Cache::get($ck);
        if ($ret !== false) {
            $ret = json_decode($ret, true);
        } else {
            $ret = DB::getDB($fromDb)->fetchOne(
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

    public static function goodsName($goodsId)
    {
        $goodsInfo = self::findGoodsById($goodsId);
        return empty($goodsInfo) ? '' : $goodsInfo['name'];
    }

    public static function getSomeGoodsByCategory(
        $goodsId,
        $categoryId,
        $page,
        $size
    ) {
        if (empty($goodsId) || empty($categoryId) || $size <= 0) {
            return array();
        }
        $page = $page > 0 ? $page - 1 : $page;
        $ret = DB::getDB()->fetchSome(
            'g_goods',
            '*',
            array('goods_id', 'category_id', 'state'),
            array($goodsId, $categoryId, self::GOODS_ST_UP),
            array('and', 'and'),
            array('sort'), array('desc'),
            array($page * $size, $size)
        );
        return empty($ret) ? array() : $ret;
    }
    
    public static function doLikeGoods($goodsId)
    {
        if (empty($goodsId)) {
            return false;
        }

        $sql = 'update g_goods set like_count = like_count + 1 where goods_id = ' . $goodsId;
        $ret = DB::getDB('w')->rawExec($sql);
        if ($ret === false) {
            return false;
        }
        self::onUpdateData($goodsId);
        return $ret > 0 ? true : false;
    }

    private static function onUpdateData($goodsId)
    {
        Cache::del(Cache::CK_GOODS_INFO . $goodsId);
        self::findGoodsById($goodsId, 'w');
    }
}

