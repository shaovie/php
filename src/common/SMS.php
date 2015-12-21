<?php
/**
 * @Author shaowei
 * @Date   2015-12-01
 */

namespace src\common;

use \src\job\model\AsyncModel;

class SMS
{
    const SMS_TAG = 'xx';

    public static function genVerifyCode()
    {
        return mt_rand(1000, 9999);
    }

    public static function isVerifyCode($code)
    {
        if (empty($code)) {
            return false;
        }
    }

    public static function verifyCode($phone, $code)
    {
        if (empty($phone) || empty($code)) {
            return false;
        }
        $content = '您的验证码为：' . $code . '，有效期30分钟。非本人操作请忽略该信息';
        AsyncModel::asyncSendSms($phone, $content);
    }

    // ensms
    private static function secondSend($smsPhones, $smsContent)
    {
        $smsUser    = urlencode('xxxx'); // TODO
        $smsPasswd  = urlencode('xxx');  // TODO
        $now = time();
        $smsSendtime = $now;
        $url = 'http://sms.ensms.com:8080/sendsms/?username=' . $smsUser
            . '&pwd=' . md5($smsUser . $smsPasswd . $smsSendtime) 
            . '&dt=' . $now
            . '&msg=' . urlencode(self::SMS_TAG . $smsContent)
            . '&mobiles=' . urlencode($smsPhones)
            . '&code=';

        $beginTime = microtime(true);
        $ret = HttpUtil::request($url, false, false, 3);
        $diff = round(microtime(true) - $beginTime, 3);
        if ((float)$diff > 1.5) {
            Log::warng('first smservice - escape long time ' . $diff);
        }
        if ($ret === false) {
            return false;
        }
        
        $ret = intval($ret);
        if ($ret != 0) {
            $state = '接口发回：';
            switch ($ret) {
            case -1:
                $state .= '一次发送的手机号码过多';
                break;
            case -2:
                $state .= '登录账户错误';
                break;
            case -3:
                $state .= '密码错误';
                break;
            case -4:
                $state .= '余额不足 ';
                AsyncModel::monitor('短信余额不足', '备用短信运营商余额不足');
                break;
            case -5:
                $state .= '超时[注意检查服务器系统时间]';
                break;
            case -6:
                $state .= 'code参数不合法';
                break;
            case -7:
                $state .= '用成POST了，正确应该是GET';
                break;
            case -8:
                $state .= 'username参数丢失';
                break;
            case -9:
                $state .= 'pwd参数丢失';
                break;
            case -10:
                $state .= 'msg参数丢失 或者 msg为空信息 或 msg 编码不对';
                break;
            case -11:
                $state .= 'mobiles参数丢失';
                break;
            case -12:
                $state .= 'dt参数丢失';
                break;
            case -13:
                $state .= '一次下发短信超过了400个字';
                break;
            case -14:
                $state .= 'mobiles参数不对 不是正确电话号';
                break;
            }
            Log::fatal('sms - ' . $state);
            return false;
        }
        return true;
    }

    // ctysms
    private static function secondSend($smsPhones, $smsContent)
    {
        $url = 'http://si.800617.com:4400/SendSms.aspx?un=xxx&pwd=xxx' // TODO
            . '&mobile=' . $smsPhones
            . '&msg=' . urlencode(iconv('utf-8', 'gb2312', $smsContent));

        $beginTime = microtime(true);
        $ret = HttpUtil::request($url, false, false, 3);
        $diff = round(microtime(true) - $beginTime, 3);
        if ((float)$diff > 1.5) {
            Log::warng('second smsservice - escape long time ' . $diff);
        }
        if ($ret === false) {
            return false;
        }
        if (strpos($ret, '=1&') !== false) {
            return true;
        }
        if (strpos($ret, '=-11&') !== false) { // 无余额
            AsyncModel::monitor('短信余额不足', '首选短信运营商余额不足');
            return false;
        }
        return false;
    }
}
