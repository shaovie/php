<?php
/**
 * @Author shaowei
 * @Date   2015-11-30
 */

namespace src\user\model;

user \src\common\Nosql;
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
        $nk  = Nosql::NK_MONITOR_LOG . $title . ':' . $desc;
        $ret = Nosql::get($nk);
        if ($ret !== false) {
            return ;
        }
        Nosql::setex($nk, Nosql::NK_MONITOR_LOG_EXPIRE, 'x');

        $nk = Nosql::NK_ASYNC_EMAIL_QUEUE;
        $data = array(
            'toList' => array(
                'xxx@xx.com',
            ),
            'title'  => $title,
            'desc'   => $desc,
            'mailid' => Util::getRandomStr(16),
        );
        Nosql::rPush($nk, json_encode($data));
    }

    public static function asyncSendTplMsg($openid, $data)
    {
        if (empty($openid)) {
            return ;
        }
        $nk = Nosql::NK_ASYNC_SEND_TPL_MSG_QUEUE . ':'
            . (abs(Util::ascIIStrToInt($openid)) % ASYNC_SEND_TPL_MSG_QUEUE_SIZE);
        Nosql::rPush($nk, json_encode($data, JSON_UNESCAPED_UNICODE));
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
        $nk = Nosql::NK_ASYNC_SEND_KF_MSG_QUEUE . ':'
            . (abs(Util::ascIIStrToInt($openid)) % ASYNC_SEND_KF_MSG_QUEUE_SIZE);
        Nosql::rPush($nk, json_encode($data));
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
        $nk = Nosql::NK_ASYNC_SMS_QUEUE . ':'
            . (abs(Util::ascIIStrToInt($phone)) % ASYNC_SEND_SMS_QUEUE_SIZE);
        Nosql::rPush($nk, json_encode($data));
    }
}

