<?php
/**
 * @Author shaowei
 * @Date   2015-12-01
 */

namespace src\job\controller;

use \src\common\SMS;
use \src\common\Nosql;
use \src\common\Log;

class SendSMSController extends JobController
{

    public function send()
    {
        $this->spawnTask(AsyncModel::ASYNC_SEND_SMS_QUEUE_SIZE);
    }

    protected function run($idx)
    {
        $nk = Nosql::NK_ASYNC_SMS_QUEUE . ':' . $idx;
        $beginTime = time();

        do {
            do {
                $rawMsg = Nosql::lPop($nk);
                if ($rawMsg === false
                    || !isset($rawMsg[0])) {
                    break;
                }
                $data = json_decode($rawMsg, true);
                $ret = SMS::firstSend($data['phone'], $data['content']);
                if ($ret === false) {
                    SMS::secondSend($data['phone'], $data['content']); // 换成不同的运营商
                }
            } while (true);

            if (time() - $beginTime > 30) {
                break;
            }
            usleep(200000);
        } while (true);
    }
}

