<?php
/**
 * @Author shaowei
 * @Date   2015-11-30
 */

namespace src\job\controller;

use \src\common\Cache;
use \src\common\WxSDK;
use \src\common\Log;

class SendKfMsgController extends JobController
{
    public function send()
    {
        $this->spawnTask(ASYNC_SEND_KF_MSG_QUEUE_SIZE);
    }

    protected function run($idx)
    {
        $beginTime = time();
        do {
            $failMap = array();
            $ck = Cache::CK_ASYNC_SEND_KF_MSG_QUEUE . ':' . $idx;
            do {
                $rawMsg = Cache::lPop($ck);
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
                    $failKey = $data['openid'] . $data['time'];
                    if (isset($failMap[$failKey])) {
                        if ($failMap[$failKey] > 2) {
                            continue; // drop it
                        }
                        $failMap[$failKey] = $failMap[$failKey] + 1;
                        Cache::lPush($ck, $rawMsg);
                    } else {
                        $failMap[$failKey] = 1;
                        Cache::lPush($ck, $rawMsg);
                    }
                }
            } while (true);

            if (time() - $beginTime > 30) {
                break;
            }
            usleep(500000);
        } while (true);
    }
}

