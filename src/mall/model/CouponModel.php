<?php
/**
 * @Author shaowei
 * @Date   2015-12-27
 */

namespace src\mall\model;

use \src\common\Cache;
use \src\common\Util;
use \src\common\Log;
use \src\common\DB;

class CouponCfgModel
{
    const COUPON_ST_INVALID  = 0; // 无效
    const COUPON_ST_VALID    = 1; // 有效

    public static function newOne(
        $beginTime,
        $endTime,
        $name,
        $remark,
        $couponAmount,
        $orderAmount,
        $categoryId
    ) {
        if (empty($name)) {
            return false;
        }

        $data = array(
            'begin_time' => $beginTime,
            'end_time' => $endTime,
            'name' => $name,
            'remark' => $remark,
            'coupon_amount' => $couponAmount,
            'order_amount' => $orderAmount,
            'category_id' => $categoryId,
            'state' => self::COUPON_ST_INVALID,
            'ctime' => CURRENT_TIME,
        );
        $ret = DB::getDB('w')->insertOne('m_coupon_cfg', $data);
        if ($ret === false || (int)$ret <= 0) {
            return false;
        }
        return true;
    }

    public static function findCouponById($couponId)
    {
        if (empty($couponId)) {
            return array();
        }
        $ck = Cache::CK_COUPON_CFG_INFO . $couponId;
        $ret = Cache::get($ck);
        if ($ret !== false) {
            $ret = json_decode($ret, true);
        } else {
            $ret = DB::getDB()->fetchOne(
                'm_coupon_cfg',
                '*',
                array('id'), array($couponId)
            );
            if ($ret !== false) {
                Cache::setex($ck, json_encode($ret), Cache::CK_COUPON_CFG_INFO_EXPIRE);
            }
        }
        return $ret === false ? array() : $ret;
    }

    public static function findSomeCouponsByIds($couponIds)
    {
        if (empty($couponIds)) {
            return array();
        }
        ksort($couponIds, SORT_NUMERIC);
        $idSet = implode(',', $couponIds);
        $ck = Cache::CK_COUPON_CFG_LIST_INFO . $idSet;
        $ret = Cache::get($ck);
        if ($ret !== false) {
            $ret = json_decode($ret, true);
        } else {
            $sql = "select * from m_coupon_cfg where id in ($idSet)";
            $ret = DB::getDB()->rawQuery($sql);
            if ($ret !== false) {
                Cache::setex($ck, json_encode($ret), Cache::CK_COUPON_CFG_INFO_LIST_EXPIRE);
            }
        }
        return $ret === false ? array() : $ret;
    }
}
