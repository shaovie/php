<?php
/**
 * @Author shaowei
 * @Date   2015-12-23
 */

namespace src\mall\model;

use \src\common\Cache;
use \src\common\Log;

class CartModel
{
    public static function newOne($userId, $goodsId, $content, $amount, $attach)
    {
        if (empty($goodsId)
            || empty($userId)
            || empty($amount)) {
            return false;
        }

        $data = array(
            'user_id'   => $userId,
            'goods_id'  => $goodsId,
            'sku_info'  => $skuInfo,
            'amount'    => $amount,
            'attach'    => $attach,
            'ctime'     => CURRENT_TIME,
        );
        $ret = Db::getDB('w')->insertOne('m_cart', $data);
        if ($ret === false) {
            return false;
        }
        self::onUpdateData($userId);
        return true;
    }

    public static function getCartAmount($userId)
    {
        $ret = self::getCartList($userId);
        return count($ret);
    }

    public static function getCartList($userId, $fromDb = 'w')
    {
        if (empty($userId)) {
            return array();
        }
        $ck = Cache::CK_CART_LIST . $userId;
        $ret = Cache::get($ck);
        if ($ret !== false) {
            $ret = json_decode($ret, true);
        } else {
            $ret = DB::getDB($fromDb)->fetchAll(
                'm_cart',
                '*',
                array('user_id'), array($userId),
            );
            if ($ret !== false) {
                Cache::setex($ck, Cache::CK_CART_LIST_EXPIRE, json_encode($ret));
            }
        }
        if (empty($ret)) {
            return array();
        }
        return $ret;
    }

    public static function delCart($userId, $cartId)
    {
        if (empty($userId)
            || empty($cartId)) {
            return false;
        }
        $ret = DB::getDB('w')->delete(
            'm_cart',
            array('id', 'user_id'), array($cartId, $userId),
            array('and')
        );
        if ($ret === false) {
            return false;
        }
        self::onUpdateData($userId);
        return true;
    }

    private static function onUpdateData($userId)
    {
        Cache::del(Cache::CK_CART_LIST . $userId);
        self::getCartList($userId, 'w');
    }
}

