<?php
/**
 * @Author shaowei
 * @Date   2015-09-17
 */

namespace src\pay\model;

class PayModel
{
    const PAY_TYPE_WX    = 1; // 微信
    const PAY_TYPE_ALI   = 2; // 支付宝

    public static function onCreateOrderOk(
        $orderId,
        $orderAttach
    ) {
        // 构造一个订单业务数据集，用来后续业务使用，针对一些不敏感的数据
        $nk = Nosql::NK_ORDER_ATTACH_INFO . $orderId;
        Nosql::setex($nk, Nosql::NK_ORDER_ATTACH_INFO_EXPIRE, json_encode($orderAttach));
    }
}


