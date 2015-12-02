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
use \src\user\model\UserModel;
use \src\user\model\WxUserModel;

class WxEventAsyncController extends JobController
{
    public function send()
    {
        $this->spawnTask(AsyncModel::ASYNC_WX_EVENT_QUEUE_SIZE);
    }

    protected function run($idx)
    {
        $failMap = array();
        $nk = Nosql::NK_ASYNC_WX_EVENT_QUEUE . $idx;
        $beginTime = time();

        do {
            do {
                $rawMsg = Nosql::lPop($nk);
                if ($rawMsg === false
                    || !isset($rawMsg[0])) {
                    break;
                }
                $data = json_decode($rawMsg, true);
                $this->processEvent($data);
            } while (true);

            if (time() - $beginTime > 30) { // 30秒脚本重新执行一次
                break;
            }
            usleep(200000);
        } while (true);
    }

    private function processEvent($data)
    {
        switch ($data['event']) {
        case 'subscribe':
            $this->onSubscribe($data['openid'], $data['from']);
            break;
        default:
            Log::error('wx event async job: unknow event');
        }
    }

    private function onSubscribe($openid, $from)
    {
        $wxUserInfo = WxSDK::getUserInfo($openid, 'snsapi_base');
        if (empty($wxUserInfo)) {
            $wxUserInfo = WxSDK::getUserInfo($openid, 'snsapi_base');
            if (empty($wxUserInfo)) {
                Log::warng('get wx:' . $openid . ' userinfo fail where on ' . $from);
                return false;
            }
        }

        $userInfo = WxUserModel::findUserByOpenId($openid);
        if (empty($userInfo)) {
            WxUserModel::newWxUser($wxUserInfo);
        } else {
            WxUserModel::updateWxUserInfo($userInfo, $wxUserInfo);
        }
    }
}

