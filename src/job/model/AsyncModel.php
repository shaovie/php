<?php
/**
 * @Author shaowei
 * @Date   2015-11-30
 */

namespace src\job\model;

use \src\common\Nosql;
use \src\common\Util;

class AsyncModel
{
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
                'xxx@xx.com', // TODO
            ),
            'title'  => $title,
            'desc'   => $desc,
            'mailid' => Util::getRandomStr(16),
        );
        Nosql::rPush($nk, json_encode($data));
    }

    // 马上发送 pushTime = 0，否则置成绝对时间
    public static function asyncSendTplMsg($openid, $msg, $pushTime)
    {
        if (empty($openid) || empty($msg)) {
            return ;
        }
        $data = array(
            'msgtype' => 'tpl',
            'msgid' => $openid . Util::getRandomStr(6),
            'pushTime' => $pushTime
            'data' => $msg,
        );
        $nk = Nosql::NK_ASYNC_SEND_WX_MSG_QUEUE;
        if ($pushTime > 0) {
            $nk = Nosql::NK_ASYNC_TIMEDSEND_WX_MSG_QUEUE;
        }
        Nosql::rPush($nk, json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    // 马上发送 pushTime = 0，否则置成绝对时间
    public static function asyncSendKfMsg($openid, $msgtype, $content, $pushTime)
    {
        if (empty($openid)) {
            return ;
        }
        $data = array(
            'msgtype' => 'kf',
            'msgid' => $openid . Util::getRandomStr(6),
            'pushTime' => $pushTime,
            'data' => array(
                'openid' => $openid,
                'msgtype' => $msgtype,
                'content' => $content,
            ),
        );
        $nk = Nosql::NK_ASYNC_SEND_WX_MSG_QUEUE;
        if ($pushTime > 0) {
            $nk = Nosql::NK_ASYNC_TIMEDSEND_WX_MSG_QUEUE;
        }
        Nosql::rPush($nk, json_encode($data));
    }

    public static function asyncSendSMS($phone, $content)
    {
        if (empty($phone)) {
            return ;
        }
        $data = array(
            'phone'   => $phone,
            'content' => $content,
        );
        $nk = Nosql::NK_ASYNC_SMS_QUEUE;
        Nosql::rPush($nk, json_encode($data));
    }

    // 用于创建或更新用户信息
    public static function asyncSubscribe($openid, $from)
    {
        $data = array(
            'event' => 'subscribe',
            'openid' => $openid,
            'from' => $from,
        );
        $nk = Nosql::NK_ASYNC_WX_EVENT_QUEUE;
        Nosql::rPush($nk, json_encode($data));
    }

    public static function asyncDBOpt($opt, $data)
    {
        $data = array(
            'opt' => $opt,
            'data' => $data,
        );
        $nk = Nosql::NK_ASYNC_DB_OPT_QUEUE;
        Nosql::rPush($nk, json_encode($data));
    }

    public static function asyncCreateOrder($userId, $orderType, $data)
    {
        $data = array(
            'userId' => $userId,
            'orderType' => $orderType,
            'data' => $data,
        );
        $nk = Nosql::NK_ASYNC_ORDER_QUEUE;
        Nosql::rPush($nk, json_encode($data));
    }

    public static function asyncOrderPayRemind($orderId)
    {
        $data = array(
            'orderId' => $orderId,
            'ctime' => CURRENT_TIME,
        );
        $nk = Nosql::NK_ASYNC_ORDER_PAY_REMIND_QUEUE;
        Nosql::rPush($nk, json_encode($data));
    }

    public static function asyncCancelOrder($orderId, $duration)
    {
        $data = array(
            'orderId' => $orderId,
            'ctime' => CURRENT_TIME,
        );
        $nk = Nosql::NK_ASYNC_CANCEL_ORDER_QUEUE;
        Nosql::rPush($nk, json_encode($data));
    }

    public static function orderQueueSize()
    {
        $ret = Nosql::lSize(Nosql::NK_ASYNC_ORDER_QUEUE);
        return (int)$ret;
    }
}

