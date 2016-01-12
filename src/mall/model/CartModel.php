<?php
/**
 * @Author shaowei
 * @Date   2015-12-23
 */

namespace src\mall\model;

use \src\common\Cache;
use \src\common\Log;
use \src\common\DB;
use \src\user\model\UserCartModel;

class CartModel
{
    const MAX_CART_GOODS_AMOUNT = 15;

    // 获得购物车列表
    public static function getCartList($userId)
    {
        if ($userId <= 0) {
            return array();
        }

        $cartList = UserCartModel::getCartList($userId);
        if (empty($cartList)) {
            return array();
        }

        $cartResult = array();
        foreach ($cartList as $cartGoods) {
            $data = self::fillCartGoodsInfo($cartGoods);
            $cartResult[] = $data;
        }
        return $cartResult;
    }

    public static function fillCartGoodsInfo($cartGoods)
    {
        $data = array();
        $goodsInfo = GoodsModel::findGoodsById($cartGoods['goods_id']);
        if (empty($goodsInfo)) {
            return $data;
        }

        $data['goodsId'] = $cartGoods['goods_id'];
        $data['amount']  = $cartGoods['amount'];
        $data['salePrice'] = $goodsInfo['sale_price'];
        $data['goodsName'] = $goodsInfo['name'];
        $data['imageUrl'] = $goodsInfo['image_url'];
        return $data;
    }
}

