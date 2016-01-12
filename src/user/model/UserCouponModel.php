<?php
/**
 * @Author shaowei
 * @Date   2015-12-27
 */

namespace src\user\model;

use \src\common\DB;
use \src\common\Util;
use \src\common\Cache;
use \src\mall\model\GoodsCategoryModel;

class UserCouponModel
{
    const COUPON_ST_UNUSED = 0;  // 未使用
    const COUPON_ST_USED   = 1;  // 已使用

    public static function newOne(
        $userId,
        $couponId,
        $beginTime,
        $endTime,
        $name,
        $remark,
        $couponAmount,
        $orderAmount,
        $categoryId
    ) {
        if (empty($userId) || empty($couponId)) {
            return false;
        }

        $data = array(
            'user_id' => $userId,
            'coupon_id' => $couponId,
            'use_time' => 0,
            'state' => self::COUPON_ST_UNUSED,
            'begin_time' => $beginTime,
            'end_time' => $endTime,
            'name' => $name,
            'remark' => $remark,
            'coupon_amount' => $couponAmount,
            'order_amount' => $orderAmount,
            'category_id' => $categoryId,
            'ctime' => CURRENT_TIME,
        );
        $ret = DB::getDB('w')->insertOne('u_coupon', $data);
        if ($ret === false || (int)$ret <= 0) {
            return false;
        }
        return true;
    }

    public static function getCouponById($userId, $couponId)
    {
        if (empty($userId) || empty($couponId)) {
            return false;
        }
        $ret = DB::getDB()->fetchOne(
            'u_coupon',
            '*',
            array('user_id', 'coupon_id'), array($userId, $couponId),
            array('and')
        );
        return $ret === false ? array() : $ret;
    }

    public static function getSomeUnusedCoupon($userId, $page, $size)
    {
        return self::getSomeCoupon($userId, self::COUPON_ST_UNUSED, $page, $size);
    }

    public static function getSomeUsedCoupon($userId, $page, $size)
    {
        return self::getSomeCoupon($userId, self::COUPON_ST_USED, $page, $size);
    }

    public static function getSomeExpiredCoupon($userId, $page, $size)
    {
        if (empty($userId)) {
            return array();
        }

        $page = $page > 0 ? $page - 1 : $page;

        $ret = DB::getDB()->fetchSome(
            'u_coupon',
            '*',
            array('user_id', 'end_time<='), array($userId, CURRENT_TIME),
            array('and'),
            array('end_time'), array('desc'),
            array($page * $size, $size)
        );

        return $ret === false ? array() : $ret;
    }

    public static function useCoupon($userId, $couponId)
    {
        if (empty($userId) || empty($couponId)) {
            return false;
        }
        $data = array('state' => self::COUPON_ST_USED, 'use_time' => CURRENT_TIME);
        $ret = DB::getDB('w')->update(
            'u_coupon',
            $data,
            array('user_id', 'coupon_id', 'state'),
            array($userId, $couponId, self::COUPON_ST_UNUSED),
            array('and', 'and'),
            1
        );
        if ($ret === false) {
            return false;
        }
        return $ret > 0;
    }

    public static function getAvalidCouponListForOrder($userId, $goodsList)
    {
        if (empty($userId) || empty($goodsList)) {
            return array();
        }
        $ret = DB::getDB()->fetchAll(
            'u_coupon',
            '*',
            array('user_id', 'state', 'begin_time <', 'end_time >'),
            array($userId, self::COUPON_ST_UNUSED, CURRENT_TIME, CURRENT_TIME),
            array('and', 'and', 'and')
        );

        if ($ret === false) {
            return array();
        }
        $couponList = array();
        foreach ($ret as $coupon) {
            $totalPrice = 0.0;
            foreach ($goodsList as $goods) {
                if ($coupon['category_id'] == 0 // 无品类限制
                    || GoodsCategoryModel::checkBelongCategoryOrNot(
                        $coupon['category_id'],
                        $goods['category_id'])
                ) {
                    $totalPrice += $goods['sale_price'];
                }
            }
            if ($totalPrice > 0.0001) {
                if ((float)$coupon['order_amount'] < 0.0001 // 不限制订单金额
                    || (float)$totalPrice >= (float)$coupon['order_amount']) {
                    $couponList[] = $coupon;
                }
            }
        }
        return $couponList;
    }

    public static function getBestCoupon($couponList)
    {
        if (empty($couponList)) {
            return array();
        }
        $func = function($v1, $v2) {
            if ((float)$v1['coupon_amount'] == (float)$v2['coupon_amount']) {
                return 0;
            }
            return (float)$v1['coupon_amount'] > (float)$v2['coupon_amount'] ? 1 : -1;
        };
        $ret = usort($couponList, $func);
        return end($ret);
    }

    // 计算优惠券优惠金额
    public static function calcCouponPayAmount($couponInfo, $goodsList)
    {
        $result = array('code' => ERR_OPT_FAIL, 'desc' => '', 'result' => array());
        if ($couponInfo['state'] == self::COUPON_ST_USED) {
            $result['desc'] = '优惠券已使用';
            return $result;
        }
        if ($couponInfo['begin_time'] > CURRENT_TIME
            || $couponInfo['end_time'] < CURRENT_TIME) {
            $result['desc'] = '优惠券不在有效期内';
            return $result;
        }

        $totalPrice = 0.0;
        foreach ($goodsList as $goods) {
            if ($couponInfo['category_id'] == 0 // 无品类限制
                || GoodsCategoryModel::checkBelongCategoryOrNot(
                    $couponInfo['category_id'],
                    $goods['category_id'])
            ) {
                $totalPrice += $goods['sale_price'];
            }
        }
        if ($totalPrice < 0.0001) {
            $result['desc'] = '优惠券品类不符，不能使用';
            return $result;
        }
        if ((float)$couponInfo['order_amount'] > 0.0001
            && (float)$totalPrice < (float)$couponInfo['order_amount']) {
            $result['desc'] = '符合优惠券条件的订单商品金额不足，优惠券不能使用';
            return $result;
        }

        return $result;
    }

    private static function getSomeCoupon($userId, $state, $page, $size)
    {
        if (empty($userId)) {
            return array();
        }

        $page = $page > 0 ? $page - 1 : $page;

        $ret = DB::getDB()->fetchSome(
            'u_coupon',
            '*',
            array('user_id', 'state'), array($userId, self::COUPON_ST_UNUSED),
            array('and'),
            array('begin_time'), array('asc'),
            array($page * $size, $size)
        );

        return $ret === false ? array() : $ret;
    }
}

