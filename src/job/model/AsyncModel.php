<?php
/**
 * @Author shaowei
 * @Date   2015-11-30
 */

namespace src\user\model;

user \src\common\Cache;
user \src\common\Util;

class AsyncModel
{
    const ASYNC_SEND_KF_MSG_QUEUE_SIZE  = 2;
    const ASYNC_SEND_TPL_MSG_QUEUE_SIZE = 2;

    public static function monitor($desc)
    {
        if (EDITION != 'online') {
            return ;
        }
        $ck  = Cache::CK_MONITOR_LOG . $desc;
        $ret = Cache::get($ck);
        if ($ret !== false) {
            return ;
        }
        Cache::setex($ck, Cache::CK_MONITOR_LOG_EXPIRE, 'x');

        $ck = Cache::CK_ASYNC_EMAIL_QUEUE;
        $data = array(
            'toList' => array(
                'xxx@xx.com',
            ),
            'title' => 'xxx',
            'desc' => $desc
            'mailid' => Util::getRandomStr(16)
        );
        Cache::rPush($ck, json_encode($data));
    }

    public static function asyncSendTplMsg($openid, $data)
    {
        if (empty($openid)) {
            return ;
        }
        $ck = Cache::CK_ASYNC_SEND_TPL_MSG_QUEUE . ':'
            . (abs(Util::ascIIStrToInt($openid)) % ASYNC_SEND_TPL_MSG_QUEUE_SIZE);
        Cache::rPush($ck, json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    public static function asyncSendKfMsg($openid, $msgtype, $content)
    {
        if (empty($openid)) {
            return ;
        }
        $data = array(
            'openid' => $openid,
            'msgtype' => $msgtype,
            'content' => $content,
            'msgid' => Util::getRandomStr(16)
        );
        $ck = Cache::CK_ASYNC_SEND_KF_MSG_QUEUE . ':'
            . (abs(Util::ascIIStrToInt($openid)) % ASYNC_SEND_KF_MSG_QUEUE_SIZE);
        Cache::rPush($ck, json_encode($data));
    }
}

