<?php
/**
 * @Author shaowei
 * @Date   2015-12-23
 */

namespace src\user\model;

use \src\common\Nosql;
use \src\common\Cache;
use \src\common\Log;
use \src\common\DB;
use \src\mall\model\GoodsModel;
use \src\mall\model\GoodsSKUModel;
use \src\user\model\UserModel;
use \src\pay\model\PayModel;

class UserOrderModel
{
    const MAX_ATTACH_LEN        = 255;

    // 订单前缀
    const ORDER_PRE_COMMON      = '01';  // 普通单品订单

    // 订单状态
    const ORDER_ST_CREATED      = 0;
    const ORDER_ST_FINISHED     = 1;
    const ORDER_ST_CANCELED     = 2;

    // 下单环境
    const ORDER_ENV_IOS         = 1;
    const ORDER_ENV_IOS         = 1;
    const ORDER_ENV_ANDROID     = 2;
    const ORDER_ENV_WEIXIN      = 3;

    public static function newOne(
        $orderId,
        $orderEnv,
        $userId,
        $reName,
        $rePhone,
        $addrType,
        $provinceId,
        $cityId,
        $districtId,
        $detail,
        $reIdCard,
        $payState,
        $orderAmount,
        $olPayAmount,
        $acPayAmount,
        $olPayType,
        $couponPayAmount,
        $couponId,
        $postage,
        $attach
    ) {
        if (empty($orderId)
            || empty($orderEnv)
            || empty($userId)) {
            return false;
        }

        $data = array(
            'order_id' => $orderId,
            'user_id' => $userId,
            're_name' => Util::emojiEncode($reName),
            're_phone' => $rePhone,
            'addr_type' => $addrType,
            'province_id' => $provinceId,
            'city_id' => $cityId,
            'district_id' => $districtId,
            'detail' => $detail,
            're_id_card' => $reIdCard,
            'pay_state' => $payState,
            'order_state' => self::ORDER_ST_CREATED,
            'order_amount' => $orderAmount,
            'ol_pay_amount' => $olPayAmount,
            'ac_pay_amount' => $acPayAmount,
            'ol_pay_type' => $olPayType,
            'coupon_pay_amount' => $couponPayAmount,
            'coupon_id' => $couponId,
            'postage' => $postage,
            'order_env' => $orderEnv,
            'remark' => '',
            'attach' => $attach,
            'ctime' => CURRENT_TIME,
            'mtime' => CURRENT_TIME,
            'm_user' => 'sys'
        );
        $ret = DB::getDB('w')->insertOne('o_order', $data);
        if ($ret === false || (int)$ret <= 0) {
            return false;
        }
        self::onUpdateData($orderId);
        return true;
    }

    public static function findOrderByOrderId($orderId, $fromDb = 'w')
    {
        if (empty($orderId)) {
            return array();
        }
        $ck = Cache::CK_ORDER_INFO . $orderId;
        $ret = Cache::get($ck);
        if ($ret !== false) {
            $ret = json_decode($ret, true);
        } else {
            $ret = DB::getDB($fromDb)->fetchOne(
                'o_order',
                '*',
                array('order_id'), array($orderId)
            );
            if ($ret !== false) {
                Cache::setex($ck, Cache::CK_ORDER_INFO_EXPIRE, json_encode($ret));
            }
        }
        if (empty($ret)) {
            return array();
        }
        $ret['re_name'] = Util::emojiDecode($ret['re_name']);
        return $ret;
    }

    public static function cancelOrder($userId, $orderId)
    {
        if (empty($userId) || empty($orderId)) {
            return false;
        }

        $ret = DB::getDB('w')->update(
            'o_order',
            array('order_state' => self::ORDER_ST_CANCELED),
            array('order_id', 'user_id', 'order_state'),
            array($userId, $orderId, self::ORDER_ST_CREATED)
        );
        if ($ret === false || (int)$ret <= 0) {
            return false;
        }
        self::onUpdateData($orderId);
        return true;
    }

    public static function genOrderId($prefix, $userId)
    {
        for ($i = 0; $i < 10; $i++) {
            $orderId = $prefix
                . date('ymd', CURRENT_TIME)
                . str_pad(mt_rand(1, 999999), 7, '0', STR_PAD_LEFT)
                . str_pad(($userId % 100), 2, '0', STR_PAD_LEFT);
            $nk = Nosql::NK_ORDER_ID_RECORD . $orderId;
            $ret = Nosql::get($nk);
            if (empty($ret)) {
                Nosql::setex($nk, Nosql::NK_ORDER_ID_RECORD_EXPIRE, 'x');
                return $orderId;
            }
        }
        Log::fatal('gen order id fail! prefix = ' . $prefix . ' user id = ' . $userId);
        return '';
    }


    private static function onUpdateData($orderId)
    {
        Cache::del(Cache::CK_ORDER_INFO . $orderId);
        self::findOrderByOrderId($orderId, 'w');
    }
}

