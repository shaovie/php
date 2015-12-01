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
    const ASYNC_SEND_SMS_QUEUE_SIZE     = 2;

    public static function monitor($title, $desc)
    {
        if (EDITION != 'online') {
            return ;
        }
        $ck  = Nosql::NK_MONITOR_LOG . $title . ':' . $desc;
        $ret = Cache::get($ck);
        if ($ret !== false) {
            return ;
        }
        Cache::setex($ck, Nosql::NK_MONITOR_LOG_EXPIRE, 'x');

        $ck = Nosql::NK_ASYNC_EMAIL_QUEUE;
        $data = array(
            'toList' => array(
                'xxx@xx.com',
            ),
            'title'  => $title,
            'desc'   => $desc,
            'mailid' => Util::getRandomStr(16),
        );
        Cache::rPush($ck, json_encode($data));
    }

    public static function asyncSendTplMsg($openid, $data)
    {
        if (empty($openid)) {
            return ;
        }
        $ck = Nosql::NK_ASYNC_SEND_TPL_MSG_QUEUE . ':'
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
            'msgid' => Util::getRandomStr(16),
        );
        $ck = Nosql::NK_ASYNC_SEND_KF_MSG_QUEUE . ':'
            . (abs(Util::ascIIStrToInt($openid)) % ASYNC_SEND_KF_MSG_QUEUE_SIZE);
        Cache::rPush($ck, json_encode($data));
    }

    public static function asyncSendSms($phone, $content)
    {
        if (empty($phone)) {
            return ;
        }
        $data = array(
            'phone'   => $phone,
            'content' => $desc,
        );
        $ck = Nosql::NK_ASYNC_SMS_QUEUE . ':'
            . (abs(Util::ascIIStrToInt($phone)) % ASYNC_SEND_SMS_QUEUE_SIZE);
        Cache::rPush($ck, json_encode($data));
    }
}

