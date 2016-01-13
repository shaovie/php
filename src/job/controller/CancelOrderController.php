<?php
/**
 * @Author shaowei
 * @Date   2016-01-10
 */

namespace src\job\controller;

use \src\common\Nosql;
use \src\common\Log;
use \src\job\model\AsyncModel;
use \src\user\model\UserOrderModel;
use \src\mall\model\OrderModel;
use \src\user\model\WxUserModel;

class CancelOrderController extends JobController
{
    protected function run($idx) { }

    public function cancel()
    {
        $nk = Nosql::NK_ASYNC_CANCEL_ORDER_QUEUE;
        $beginTime = time();

        do {
            $now = time();
            do {
                $rawMsg = Nosql::lPop($nk);
                if ($rawMsg === false
                    || !isset($rawMsg[0])) {
                    break;
                }
                $data = json_decode($rawMsg, true);
                if ($now - $data['ctime'] > (int)$data['duration']) {
                    $this->doCancel($data);
                } else {
                    Nosql::lPush($nk, $rawMsg);
                }
            } while (true);

            if ($now - $beginTime > 30) { // 30秒脚本重新执行一次
                break;
            }
            sleep(1);
        } while (true);
    }

    private function doCancel($data)
    {
        $orderId = $data['orderId'];
        $orderInfo = UserOrderModel::findOrderByOrderId($orderId);
        if (empty($orderInfo)) {
            return ;
        }
        if ($orderInfo['order_state'] != UserOrderModel::ORDER_ST_CREATED) {
            return ;
        }
        OrderModel::doCancelOrder($orderId, $orderInfo['user_id']);
    }
}

