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

class SendWxMsgController extends JobController
{
    const ASYNC_SEND_WX_MSG_QUEUE_SIZE = 4;

    // 即时发送
    public function send()
    {
        $this->spawnTask(self::ASYNC_SEND_WX_MSG_QUEUE_SIZE);
    }

    // 定时发送
    public function timedSend()
    {
        $nk = Nosql::NK_ASYNC_TIMEDSEND_WX_MSG_QUEUE;
        $beginTime = time();

        do {
            $now = time();
            do {
                $rawMsg = Nosql::lPop($nk);
                if ($rawMsg === false
                    || !isset($rawMsg[0])) {
                    break ;
                }
                $data = json_decode($rawMsg, true);
                if ($now > $data['time']) {
                    Nosql::rPush(Nosql::NK_ASYNC_SEND_WX_MSG_QUEUE, $rawMsg);
                }
            } while (true);

            if ($now - $beginTime > 30) { // 30秒脚本重新执行一次
                break;
            }
            sleep(1);
        } while (true);
    }

    protected function run($idx)
    {
        $nk = Nosql::NK_ASYNC_SEND_WX_MSG_QUEUE;
        $beginTime = time();

        do {
            do {
                $rawMsg = Nosql::lPop($nk);
                if ($rawMsg === false
                    || !isset($rawMsg[0])) {
                    break;
                }
                $data = json_decode($rawMsg, true);
                $ret = $this->processMsg($data);
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

    private function processMsg($data)
    {
        switch ($data['msgtype']) {
        case 'tpl':
            return $this->sendTplMsg($data['data']);
            break;
        case 'kf':
            return $this->sendKfMsg($data['data']);
            break;
        }
        return true;
    }

    private function sendTplMsg($data)
    {
        $msg = json_encode($data, JSON_UNESCAPED_UNICODE);
        return WxSDK::sendTplMsg($msg);
    }

    private function sendKfMsg($data)
    {
        $ret = true;
        if ($data['msgtype'] == 'text') {
            $ret = WxSDK::sendKfTextMsg($data['openid'], $data['content']);
        } else if ($data['msgtype'] == 'image') {
            $ret = WxSDK::sendKfImageMsg($data['openid'], $data['content']);
        } else if ($data['msgtype'] == 'news') {
            $news = $data['content'];
            $ret = WxSDK::sendKfNewsMsg($data['openid'], $news);
        }
        return $ret;
    }
}

