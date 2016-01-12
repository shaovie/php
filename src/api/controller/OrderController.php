<?php
/**
 * @Author shaowei
 * @Date   2015-12-23
 */

namespace src\api\controller;

class OrderController extends ApiController
{
    // 立即购买
    public function immediateBuy()
    {
        $goodsId = (int)$this->postParam('goodsId', 0);
        $skuAttr = $this->postParam('skuAttr', '');
        $skuvalue = $this->postParam('skuValue', '');
        $amount = (int)$this->postParam('amount', 0);

        if ($goodsId <= 0
            || empty($skuAttr)
            || empty($skuValue)) {
            $this->ajaxReturn(ERR_PARAMS_ERROR, '参数错误');
            return ;
        }

        $result = OrderModel::createOrder(
            UserOrderModel::ORDER_PRE_COMMON,
            $this->userId()
        );
        $this->ajaxReturn(0, '', '', array('token' => $result['result']['token']));
    }

    // 快速下单
    public function quickOrder()
    {
    }
}

