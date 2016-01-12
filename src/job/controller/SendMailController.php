<?php
/**
 * @Author shaowei
 * @Date   2015-12-01
 */

namespace src\job\controller;

use \src\common\SendMail;
use \src\common\Nosql;
use \src\common\Log;

class SendMailController extends JobController
{
    public function send()
    {
        $nk = Nosql::NK_ASYNC_EMAIL_QUEUE;
        $beginTime = time();

        do {
            do {
                $rawMsg = Nosql::lPop($nk);
                if ($rawMsg === false
                    || !isset($rawMsg[0])) {
                    break;
                }
                $data = json_decode($rawMsg, true);
                $ret = SendMail::sendmail($data['toList'], $data['title'], $data['desc']);
                if ($ret === false) {
                    if (isset($data['retry'])) {
                        continue ; // drop it
                    } else {
                        $data['retry'] = 1;
                        Nosql::lPush($nk, json_encode($data));
                    }
                }
            } while (true);

            if (time() - $beginTime > 30) { // 30秒脚本重新执行一次
                break;
            }
            usleep(200000);
        } while (true);
    }

    //= protected methods
    protected function run($idx) {}
}

