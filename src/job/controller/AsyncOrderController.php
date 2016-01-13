<?php
/**
 * @Author shaowei
 * @Date   2015-12-02
 */

namespace src\job\controller;

use \src\common\Nosql;
use \src\common\Log;
use \src\job\model\AsyncModel;
use \src\mall\model\OrderModel;

class AsyncOrderController extends JobController
{
    const ASYNC_ORDER_QUEUE_SIZE = 5;

    public function createOrder()
    {
        $this->spawnTask(self::ASYNC_ORDER_QUEUE_SIZE);
    }

    protected function run($idx)
    {
        $nk = Nosql::NK_ASYNC_ORDER_QUEUE;
        $beginTime = time();

        do {
            do {
                $rawMsg = Nosql::lPop($nk);
                if ($rawMsg === false
                    || !isset($rawMsg[0])) {
                    break;
                }
                $data = json_decode($rawMsg, true);
                $this->doOpt($data);
            } while (true);

            if (time() - $beginTime > 30) { // 30秒脚本重新执行一次
                break;
            }
            usleep(200000);
        } while (true);
    }

    private function doOpt($data)
    {
        switch ($data['orderType']) {
        case UserOrderModel::ORDER_PRE_COMMON:
            $this->commenOrder($data['data']);
            break;
        default:
            Log::error('wx event async job: unknow event');
        }
    }

    private function commenOrder($data)
    {
        $nk = Nosql::NK_ASYNC_ORDER_RESULT . $data['token'];
        $result = OrderModel::doCreateOrder(
            $data['userId'],
            $data['orderPrefix'],
            // TODO
        );
        Nosql::setex($nk, Nosql::NK_ASYNC_ORDER_RESULT_EXPIRE, json_encode($result));
        if ($result['code'] == 0) {
            AsyncModel::asyncCancelOrder($result['result']['orderId'], 1800); // 普通订单超时时间1800秒
        }
    }
}

