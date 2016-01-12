<?php
/**
 * @Author shaowei
 * @Date   2015-12-23
 */

namespace src\mall\model;

use \src\common\Cache;
use \src\common\Log;
use \src\common\DB;

class OrderGoodsModel
{
    const MAX_ATTACH_LEN        = 255;

    const ST_UN_DELIVER         = 0; // 待发货
    const ST_OUT_OF_STORAGE     = 1; // 已出库
    const ST_DELIVERED          = 2; // 已发货
    const ST_RECEIVED           = 3; // 已收货

    public static function newOne(
        $orderId,
        $goodsId,
        $skuAttr,
        $skuValue,
        $amount,
        $price,
        $attach
    ) {
        if (empty($orderId)
            || empty($goodsId)
            || empty($amount)) {
            return false;
        }

        $data = array(
            'order_id' => $orderId,
            'goods_id' => $goodsId,
            'sku_attr' => $skuAttr,
            'sku_value' => $skuValue,
            'amount' => $amount,
            'price' => $price,
            'state' => self::ORDER_GOODS_ST_UN_DELIVER,
            'commented' => 0,
            'attach' => $attach,
            'ctime' => CURRENT_TIME,
            'mtime' => CURRENT_TIME,
            'm_user' => 'sys',
        );
        $ret = DB::getDB('w')->insertOne('o_order_goods', $data);
        if ($ret === false || (int)$ret <= 0) {
            return false;
        }
        return true;
    }
}

