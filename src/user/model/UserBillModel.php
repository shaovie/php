<?php
/**
 * @Author shaowei
 * @Date   2015-12-27
 */

namespace src\user\model;

use \src\common\Cache;
use \src\common\Log;

class UserBillModel
{
    const BILL_TYPE_IN  = 1;  // 收入
    const BILL_TYPE_OUT = 2;  // 支出

    const BILL_FROM_ORDER_CASH_PAY    = 100; // 余额支付
    const BILL_FROM_ORDER_OL_PAY      = 101; // 在线支付

    const BILL_FROM_ORDER_CASH_REFUND = 200; // 余额退还

    public static function newOne(
        $userId,
        $orderId,
        $billType,
        $billFrom,
        $amount,
        $leftAmount,
        $remark
    ) {
        if (empty($userId)) {
            return false;
        }

        $data = array(
            'user_id' => $userId,
            'order_id' => $orderId,
            'bill_type' => $billType,
            'bill_from' => $billFrom,
            'amount' => $amount,
            'left_amount' => $leftAmount,
            'remark' => $remark,
            'ctime' => CURRENT_TIME,
        );
        $ret = DB::getDB('w')->insertOne('u_bill', $data);
        if ($ret === false || (int)$ret <= 0) {
            return false;
        }
        return true;
    }

    public static function getSomeBill($userId, $nextId, $size)
    {
        if (empty($userId) || $size <= 0) {
            return array();
        }
        $nextId = (int)$nextId;
        if ($nextId > 0) {
            $ret = DB::getDB()->fetchSome(
                'u_bill',
                '*',
                array('user_id', 'id<'), array($userId, $nextId),
                array('and'),
                array('id'), array('desc'),
                array($size)
            );
        } else {
            $ret = DB::getDB()->fetchSome(
                'u_bill',
                '*',
                array('user_id'), array($userId),
                false,
                array('id'), array('desc'),
                array($size)
            );
        }
        return empty($ret) ? array() : $ret;
    }

    public static function getSomeInBill($userId, $nextId, $size)
    {
        return self::getSomeInOutBill($userId, self::BILL_TYPE_IN, $nextId, $size);
    }

    public static function getSomeOutBill($userId, $nextId, $size)
    {
        return self::getSomeInOutBill($userId, self::BILL_TYPE_OUT, $nextId, $size);
    }

    //= private methods
    private static function getSomeInOutBill($userId, $type, $nextId, $size)
    {
        if (empty($userId) || $size <= 0) {
            return array();
        }
        $nextId = (int)$nextId;
        if ($nextId > 0) {
            $ret = DB::getDB()->fetchSome(
                'u_bill',
                '*',
                array('user_id', 'bill_type', 'id<'), array($userId, $nextId),
                array('and', 'and'),
                array('id'), array('desc'),
                array($size)
            );
        } else {
            $ret = DB::getDB()->fetchSome(
                'u_bill',
                '*',
                array('user_id', 'bill_type'), array($userId),
                array('and'),
                array('id'), array('desc'),
                array($size)
            );
        }
        return empty($ret) ? array() : $ret;
    }
}

