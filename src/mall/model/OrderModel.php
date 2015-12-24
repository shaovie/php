<?php
/**
 * @Author shaowei
 * @Date   2015-12-23
 */

namespace src\mall\model;

use \src\common\Nosql;
use \src\common\Cache;
use \src\common\Log;

class OrderModel
{
    // 订单前缀
    const ORDER_PRE_COMMON      = '01';  // 普通单品订单

    // 订单状态
    const ORDER_ST_CREATED      = 0;
    const ORDER_ST_FINISHED     = 1;
    const ORDER_ST_CANCELED     = 2;
    const ORDER_ST_TIMEOUT      = 3;

    public static function genOrderId($prefix, $userId)
    {
        for (int $i = 0; $i < 10; $i++) {
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

    public static function newOne(
        $orderId,
        $userId,
        $reName,
        $rePhone,
        $addrType,
        $provinceId,
        $cityId,
        $districtId,
        $detail,
        $reIdCard,
        $totalAmount,
        $olPayAmount,
        $acPayAmount,
        $olPayType,
        $postage,
        $attach
    ) {
        if (empty($orderId)
            || empty($userId)) {
            return false;
        }

        $data = array(
            'order_id' => $orderId,
            'user_id' => $userId,
            're_name' => Util::emojiEncode($reName),
            're_phone' => $phone,
            'addr_type' => $addrType,
            'province_id' => $provinceId,
            'city_id' => $cityId,
            'district_id' => $districtId,
            'detail' => $detail,
            're_id_card' => $reIdCard,
            'pay_state' => PayModel::PAY_ST_UNPAY,
            'order_state' => self::ORDER_ST_CREATED,
            'total_amount' => $totalAmount,
            'ol_pay_amount' => $olPayAmount,
            'ac_pay_amount' => $acPayAmount,
            'ol_pay_type' => $olPayType,
            'postage' => $postage,
            'remark' => '',
            'attach' => $attach,
            'ctime' => CURRENT_TIME,
            'mtime' => CURRENT_TIME,
        );
        $ret = Db::getDB('w')->insertOne('m_order', $data);
        if ($ret === false) {
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
                'm_order',
                '*',
                array('order_id'), array($orderId),
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

    private static function onUpdateData($orderId)
    {
        Cache::del(Cache::CK_ORDER_INFO . $orderId);
        self::findOrderByOrderId($orderId, 'w');
    }
}

