<?php
/**
 * @Author shaowei
 * @Date   2015-12-23
 */

namespace src\mall\model;

use \src\common\Cache;
use \src\common\Log;

class OrderGoodsModel
{
    const ST_UN_DELIVER         = 0; // 待发货
    const ST_OUT_OF_STORAGE     = 1; // 已出库
    const ST_DELIVERED          = 2; // 已发货
    const ST_RECEIVED           = 3; // 已收货

    public static function newOne(
        $orderId,
        $goodsId,
        $skuInfo,
        $amount,
        $price,
        $attach,
        $mUser
    ) {
        if (empty($orderId)
            || empty($goodsId)
            || empty($amount)) {
            return false;
        }

        $data = array(
            'order_id' => $orderId,
            'goods_id' => $goodsId,
            'sku_info' => $skuInfo,
            'amount' => $amount,
            'price' => $price,
            'state' => self::ORDER_GOODS_ST_UN_DELIVER,
            'commented' => 0,
            'attach' => $attach,
            'ctime' => CURRENT_TIME,
            'mtime' => CURRENT_TIME,
            'm_user' => $mUser,
        );
        $ret = Db::getDB('w')->insertOne('u_order_goods', $data);
        if ($ret === false) {
            return false;
        }
        return true;
    }
}

