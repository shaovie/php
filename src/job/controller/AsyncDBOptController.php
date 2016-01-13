<?php
/**
 * @Author shaowei
 * @Date   2015-12-02
 */

namespace src\job\controller;

use \src\common\Nosql;
use \src\common\WxSDK;
use \src\common\Log;
use \src\job\model\AsyncModel;
use \src\user\model\WxUserModel;
use \src\user\model\UserOrderModel;

class AsyncDBOptController extends JobController
{
    const ASYNC_ORDER_QUEUE_SIZE = 3;

    public function send()
    {
        $this->spawnTask(self::ASYNC_DB_OPT_QUEUE_SIZE);
    }

    protected function run($idx)
    {
        $nk = Nosql::NK_ASYNC_DB_OPT_QUEUE;
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
        switch ($data['opt']) {
        case 'activate_for_gzh':
            WxUserModel::onActivateForGZH($data['data']['openid']);
            break;
        default:
            Log::error('wx event async job: unknow event');
        }
    }
}

