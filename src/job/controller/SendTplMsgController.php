<?php
/**
 * @Author shaowei
 * @Date   2015-08-22
 */

namespace src\job\Controller;

use \src\common\Nosql;
use \src\common\WxSDK;
use \src\common\Log;
use \src\job\model\AsyncModel;

class SendTplMsgController extends JobController
{
    public function send()
    {
        $this->spawnTask(AsyncModel::ASYNC_SEND_TPL_MSG_QUEUE_SIZE);
    }

    protected function run($idx)
    {
        $failMap = array();
        $nk = Nosql::NK_ASYNC_SEND_TPL_MSG_QUEUE . ':' . $idx;
        $beginTime = time();

        do {
            do {
                $rawMsg = Nosql::lPop($nk);
                if ($rawMsg === false
                    || !isset($rawMsg[0])) {
                    break;
                }
                $ret = WxSDK::sendTplMsg($rawMsg);
                if ($ret === false) {
                    $data = json_decode($rawMsg, true);
                    $failKey = $data['touser'] . $data['template_id'];
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
}

