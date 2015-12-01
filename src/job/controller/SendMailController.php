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
        $failMap = array();
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
                    $failKey = $data['mailid'];
                    if (isset($failMap[$failKey])) {
                        if ($failMap[$failKey] > 2) {
                            continue ; // drop it
                        }
                        $failMap[$failKey] = $failMap[$failKey] + 1;
                        Nosql::lPush($nk, $rawMsg);
                    } else {
                        $failMap[$failKey] = 1;
                        Nosql::lPush($nk, $rawMsg);
                    }
                }
            } while (true);

            if (time() - $beginTime > 30) {
                break;
            }
            usleep(200000);
        } while (true);
    }

    //= protected methods
    protected function run($idx) {}
}

