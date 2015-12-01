<?php
/**
 * @Author shaowei
 * @Date   2015-11-30
 */

namespace src\job\controller;

use \src\common\Nosql;
use \src\common\WxSDK;
use \src\common\Log;
use \src\job\model\AsyncModel;

class SendKfMsgController extends JobController
{
    public function send()
    {
        $this->spawnTask(AsyncModel::ASYNC_SEND_KF_MSG_QUEUE_SIZE);
    }

    protected function run($idx)
    {
        $failMap = array();
        $nk = Nosql::NK_ASYNC_SEND_KF_MSG_QUEUE . ':' . $idx;
        $beginTime = time();

        do {
            do {
                $rawMsg = Nosql::lPop($nk);
                if ($rawMsg === false
                    || !isset($rawMsg[0])) {
                    break;
                }
                $data = json_decode($rawMsg, true);
                $ret = -1;
                if ($data['msgtype'] == 'text') {
                    $ret = WxSDK::sendKfTextMsg($data['openid'], $data['content']);
                } else if ($data['msgtype'] == 'image') {
                    $ret = WxSDK::sendKfImageMsg($data['openid'], $data['content']);
                } else if ($data['msgtype'] == 'news') {
                    $news = $data['content'];
                    $ret = WxSDK::sendKfNewsMsg($data['openid'], $news);
                }

                if ($ret === false) {
                    $failKey = $data['msgid'];
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

