<?php
/**
 * @Author shaowei
 * @Date   2015-12-23
 */

namespace src\api\controller;

use \src\common\Check;
use \src\mall\model\GoodsSKUModel;
use \src\user\model\UserCartModel;

class CartController extends ApiController
{
    public function getCartAmount()
    {
        $cartAmount = UserCartModel::getCartAmount($this->userId());
        $this->ajaxReturn(0, '', '', array('amount' => $cartAmount));
    }

    // 加入购物车
    public function add()
    {
        $goodsId = (int)$this->postParam('goodsId', 0);
        $skuAttr = $this->postParam('skuAttr', '');
        $skuValue = $this->postParam('skuValue', '');
        $amount = (int)$this->postParam('amount', 0);

        if ($goodsId <= 0
            || !Check::isSkuAttr($skuAttr)
            || !Check::isSkuValue($skuValue)
            || $amount <= 0) {
            $this->ajaxReturn(ERR_PARAMS_ERROR, '参数错误');
            return ;
        }

        $goodsSKU = GoodsSKUModel::getSKUInfo($goodsId, $skuAttr, $skuValue);
        if (empty($goodsSKU)) {
            $this->ajaxReturn(ERR_PARAMS_ERROR, '参数错误');
            return ;
        }

        $cartAmount = UserCartModel::getCartAmount($this->userId());
        if ($cartAmount > UserCartModel::MAX_CART_GOODS_AMOUNT) {
            $this->ajaxReturn(ERR_OPT_FAIL, '您的购物车已满，赶快清理一下吧');
            return ;
        }

        $ret = UserCartModel::newOne(
            $this->userId(),
            $goodsId,
            $skuAttr,
            $skuValue,
            $amount,
            '' // attach
        );
        if ($ret === false) {
            $this->ajaxReturn(ERR_SYSTEM_ERROR, '系统错误，加入购物车失败');
            return ;
        }
        $this->ajaxReturn(0, '');
    }

    public function modifyAmount()
    {
        $cartId = (int)$this->getParam('cartId', 0);
        if ($cartId <= 0) {
            $this->ajaxReturn(ERR_PARAMS_ERROR, '参数错误');
            return ;
        }
        $amount = (int)$this->getParam('amount', 0);
        if ($amount <= 0) {
            $this->ajaxReturn(ERR_PARAMS_ERROR, '参数错误');
            return ;
        }

        UserCartModel::modifyAmount($this->userId(), $cartId, $amount);
        $this->ajaxReturn(0, '', '', array('amount' => $amount));
    }

    // 删除商品
    public function del()
    {
        $cartId = (int)$this->getParam('cartId', 0);
        if ($cartId <= 0) {
            $this->ajaxReturn(ERR_PARAMS_ERROR, '参数错误');
            return ;
        }
        UserCartModel::delCart($this->userId(), $cartId);
        $this->ajaxReturn(0, '');
    }
}

