<?php
/**
 * @Author shaowei
 * @Date   2015-12-23
 */

namespace src\mall\model;

use \src\common\Nosql;
use \src\common\Cache;
use \src\common\Log;
use \src\common\Util;
use \src\common\DB;
use \src\pay\model\PayModel;
use \src\mall\model\GoodsModel;
use \src\mall\model\PostageModel;
use \src\mall\model\GoodsSKUModel;
use \src\user\model\UserModel;
use \src\user\model\UserOrderModel;
use \src\user\model\UserBillModel;
use \src\user\model\UserCouponModel;
use \src\job\model\AsyncModel;

class OrderModel
{
    const MAX_ORDER_QUEUE_SIZE = 2000;

    public static function createOrder(
        $orderPrefix,
        $orderEnv,
        $userId
    ) {
        $optResult = array('code' => ERR_SYSTEM_ERROR, 'desc' => '', 'result' => array());
        $size = AsyncModel::orderQueueSize();
        if ($size > self::MAX_ORDER_QUEUE_SIZE) {
            $optResult['code'] = ERR_SYSTEM_BUSY;
            $optResult['desc'] = '系统正在拼命处理订单，稍等后重试...';
            return $optResult;
        }

        $token = Util::getRandomStr(16);
        $data = array(
            'token' => $token,
            'orderPrefix' => $orderPrefix,
            'orderEnv' => $orderEnv,
            // TODO
        );
        AsyncModel::asyncCreateOrder($userId, $orderPrefix, $data);

        $optResult['code'] = 0;
        $optResult['desc'] = '';
        $optResult['result'] = array('token' => $token);
        $asyncResult = array('ctime' => CURRENT_TIME);
        $nk = Nosql::NK_ASYNC_ORDER_RESULT . $token;
        Nosql::setex($nk, Nosql::NK_ASYNC_ORDER_RESULT_EXPIRE, json_encode($asyncResult);
        return $optResult;
    }

    /*=====================================业务逻辑======================================*/
    // 创建普通商品订单
    // return array('code' => 错误码, 'desc' => '错误描述', 'result' => array()) OR false
    public static function doCreateOrder(
        $orderPrefix,
        $orderEnv,
        $userId,
        $addrId,
        $goodsList, // [['goodsId' => n, 'amount' => n, 'category_id' => n,
                    // 'skuAttr' => '', 'skuValue' => '', 'attach' => ''],]
        $orderAmount,
        $useAccountPay, // 0/1 是否使用账户余额支付
        $olPayType, // 第三方支付方式
        $couponId,  // 优惠券ID
        $attach
    ) {
        $optResult = array('code' => ERR_OPT_FAIL, 'desc' => '', 'result' => array());

        if (empty($userId)) {
            $optResult['desc'] = '登录失败，不能创建订单';
            return $optResult;
        }
        if (empty($goodsList)) {
            $optResult['code'] = ERR_OPT_FAIL;
            $optResult['desc'] = '商品列表为空';
            return $optResult;
        }
        // ! 检查数据有效性
        if (strlen($attach) > OrderModel::MAX_ATTACH_LEN) {
            $optResult['code'] = ERR_SYSTEM_ERROR;
            $optResult['desc'] = '系统异常';
            Log::error('order attach too lang: ' . $attach);
            return $optResult;
        }
        foreach ($goodsList as $goods) {
            if (strlen($goods['attach']) > OrderGoodsModel::MAX_ATTACH_LEN) {
                $optResult['code'] = ERR_SYSTEM_ERROR;
                $optResult['desc'] = '系统异常';
                Log::error('order goods attach too lang: ' . $goods['attach']);
                return $optResult;
            }
        }

        // ! 检查地址
        $addrInfo = UserAddressModel::getAddr($userId, $addrId);
        if (empty($addrInfo)) {
            $optResult['desc'] = '收货地址无效';
            return $optResult;
        }

        // 准备数据
        $payState = PayModel::PAY_ST_UNPAY;
        $totalPrice = 0.0;
        $goodsListSKUInfo = array();
        foreach ($goodsList as $goods) {
            $ret = GoodsSKUModel::getSKUInfo(
                $goods['goodsId'],
                $goods['skuAttr'],
                $goods['skuValue']
            );
            if (!empty($ret)) {
                $goodsListSKUInfo[] = $ret;
                $totalPrice += (float)$ret['sale_price'];
            }
        }
        if (count($goodsList) != count($goodsListSKUInfo)) {
            $optResult['code'] = ERR_OPT_FAIL;
            $optResult['desc'] = '找不到商品或商品已下架';
            return $optResult;
        }
        if ($totalPrice <= 0.00001) {
            $optResult['code'] = ERR_OPT_FAIL;
            $optResult['desc'] = '找不到商品或商品已下架';
            return $optResult;
        }
        $newOrderId = UserOrderModel::genOrderId($orderPrefix, $userId);
        if (empty($newOrderId)) {
            $optResult['code'] = ERR_SYSTEM_BUSY;
            $optResult['desc'] = '系统繁忙，创建订单失败，请稍后重试';
            return $optResult;
        }
        // 计算优惠金额
        $couponPayAmount = 0.0;
        if ($couponId > 0) {
            $couponInfo = self::getCouponById($userId, $couponId);
            if (empty($couponInfo)) {
                $optResult['desc'] = '优惠券不存在';
                return $optResult;
            }
            $func = function ($sku, $goods) {
                return array('category_id' => $goods['category_id'], 'sale_price' => $sku['sale_price']);
            };
            $gl = array_map($func, $goodsListSKUInfo, $goodsList);
            $ret = UserCouponModel::calcCouponPayAmount($userId, $couponId, $gl);
            if ($ret['code'] != 0) {
                return $ret;
            }
            $couponPayAmount = $couponInfo['coupon_amount'];
        }
        $toPayAmount = $totalPrice - $couponPayAmount;
        if ($toPayAmount < 0.0001) {
            $optResult['desc'] = '系统计算支付金额错误，下单失败';
            return $optResult;
        }
        // 计算邮费
        $postage = PostageModel::calcPostage();

        // ! 检查库存
        foreach ($goodsListSKUInfo as $idx => $goodsSKU) {
            if ($goodsSKU['amount'] < $goodsList[$idx]['amount']) {
                if (count($goodsList) == 1) {
                    $optResult['desc'] = '商品库存不足';
                } else {
                    $optResult['desc'] = GoodsModel::goodsName($goods['goodsId']) . '库存不足';
                }
                return $optResult;
            }
        }

        //= all ok
        if (DB::getDB('w')->beginTransaction() === false) {
            $optResult['code'] = ERR_SYSTEM_ERROR;
            $optResult['desc'] = '系统异常';
            Log::error('user ' . $userId . ' create order fail! begin transaction error');
            return $optResult;
        }
        // do 扣库存
        foreach ($goodsList as $goods) {
            $ret = GoodsSKUModel::reduceInventory(
                    $goods['goodsId'],
                    $goods['skuAttr'],
                    $goods['skuValue'],
                    $goods['amount']);
            if ($ret !== true) {
                DB::getDB('w')->rollBack();
                if ($ret === false) {
                    $optResult['code'] = ERR_SYSTEM_ERROR;
                    $optResult['desc'] = '系统异常';
                    Log::error('user ' . $userId . ' create order fail! reduce inventory system error');
                } else {
                    $optResult['code'] = ERR_OPT_FAIL;
                    if (count($goodsList) == 1) {
                        $optResult['desc'] = '商品库存不足';
                    } else {
                        $optResult['desc'] = GoodsModel::goodsName($goods['goodsId']) . '库存不足';
                    }
                }
                return $optResult;
            }
        }

        // do 扣余额
        $reduceCashAmount = 0.0;
        if ($useAccountPay == 1 && $toPayAmount > 0.0001) {
            $userCash = UserModel::getCash($userId);
            if ($userCash > 0.0001) {
                $reduceCashAmount = $userCash;
                if ($userCash >= $toPayAmount) {
                    $reduceCashAmount = $toPayAmount;
                    $payState = PayModel::PAY_ST_SUCCESS;
                }
                $ret = UserModel::reduceCash($userId, $reduceCashAmount);
                if ($ret !== true) {
                    if ($ret === false) {
                        $optResult['desc'] = '系统异常，扣除余额失败';
                        Log::error('user ' . $userId . ' create order fail! reduce cash system error');
                    } else {
                        $optResult['desc'] = '余额不足';
                    }
                    DB::getDB('w')->rollBack();
                    return $optResult;
                } else {
                    $ret = UserBillModel::newOne(
                        $userId,
                        $newOrderId,
                        UserBillModel::BILL_TYPE_OUT,
                        UserBillModel::BILL_FROM_ORDER_CASH_PAY,
                        $reduceCashAmount,
                        $userCash - $reduceCashAmount,
                        ''
                    );
                    if ($ret !== true) {
                        $optResult['desc'] = '系统异常，创建订单中断';
                        Log::error('user ' . $userId . ' create order fail! insert bill system error');
                        DB::getDB('w')->rollBack();
                        return $optResult;
                    }
                }
            } else {
                DB::getDB('w')->rollBack();
                $optResult['desc'] = '余额不足';
                return $optResult;
            }
        }
        $olPayAmount = $toPayAmount - $reduceCashAmount;

        // 使用优惠券
        if ($couponPayAmount > 0.0001) {
            if (UserCouponModel::useCoupon($userId, $couponId) === false) {
                DB::getDB('w')->rollBack();
                $optResult['desc'] = '使用优惠券失败';
                return $optResult;
            }
        }

        // 创建订单
        $ret = UserOrderModel::newOne(
            $newOrderId,
            $orderEnv,
            $userId,
            $addrInfo['re_name'],
            $addrInfo['re_phone'],
            $addrInfo['addr_type'],
            $addrInfo['province_id'],
            $addrInfo['city_id'],
            $addrInfo['district_id'],
            $addrInfo['detail'],
            $addrInfo['re_id_card'],
            $payState,
            $totalPrice,
            $olPayAmount,
            $reduceCashAmount,
            $olPayType,
            $couponPayAmount,
            $couponId,
            $postage,
            $attach
        );
        if ($ret !== true) {
            DB::getDB('w')->rollBack();
            $optResult['code'] = ERR_SYSTEM_ERROR;
            $optResult['desc'] = '系统异常，创建订单失败';
            Log::error('user ' . $userId . ' create order fail! insert order system error');
            return $optResult;
        }

        foreach ($goodsListSKUInfo as $idx => $goodsSKU) {
            $ret = OrderGoodsModel::newOne(
                $newOrderId,
                $goodsSKU['goodsId'],
                $goodsSKU['sku_attr'],
                $goodsSKU['sku_value'],
                $goodsList[$idx]['amount'],
                $goodsSKU['sale_price'],
                $goodsList[$idx]['attach']
            );
            if ($ret !== true) {
                DB::getDB('w')->rollBack();
                $optResult['code'] = ERR_SYSTEM_ERROR;
                $optResult['desc'] = '系统异常，创建订单失败';
                Log::error('user ' . $userId . ' create order fail! insert order_goods system error');
                return $optResult;
            }
        }

        if (DB::getDB('w')->commit() === false) {
            $optResult['code'] = ERR_SYSTEM_ERROR;
            $optResult['desc'] = '系统异常，创建订单失败';
            return $optResult;
        }
        $optResult['code'] = 0;
        $optResult['desc'] = '';
        $optResult['result'] = array('orderId' => $newOrderId);
        return $optResult;
    }

    public static function doCancelOrder($userId, $orderId)
    {
        if (empty($userId) || empty($orderId)) {
            return false;
        }
        $orderInfo = UserOrderModel::findOrderByOrderId($orderId);
        if (empty($orderInfo)) {
            return false;
        }
        if ($orderInfo['user_id'] != $userId) {
            return false;
        }
        if ($orderInfo['user_id'] != $userId) {
            return false;
        }
        if ($orderInfo['pay_state'] != PayModel::PAY_ST_UNPAY) {
            return false;
        }

        if ($orderInfo['ac_pay_amount'] < 0.0001) {
            UserOrderModel::cancelOrder($userId, $orderId);
            return true;
        }

        // 余额退还
        if (DB::getDB('w')->beginTransaction() === false) {
            return false;
        }
        UserOrderModel::cancelOrder($userId, $orderId);
        $ret = UserModel::addCash($userId, $orderInfo['ac_pay_amount']);
        if ($ret !== true) {
            DB::getDB('w')->rollBack();
            return false;
        }
        $userCash = UserModel::getCash($userId);
        $ret = UserBillModel::newOne(
            $userId,
            $newOrderId,
            UserBillModel::BILL_TYPE_IN,
            UserBillModel::BILL_FROM_ORDER_CASH_REFUND,
            $orderInfo['ac_pay_amount'],
            $userCash + $orderInfo['ac_pay_amount'],
            'cancel order and refund cash'
        );
        if ($ret !== true) {
            DB::getDB('w')->rollBack();
            return false;
        }
        if (DB::getDB('w')->commit() === false) {
            return false;
        }
        return true;
    }
}

