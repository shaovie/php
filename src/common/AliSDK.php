<?php
/**
 * @Author shaowei
 * @Date   2015-07-20
 * http://doc.open.alipay.com/doc2/alipayDocIndex.htm
 */

namespace src\common;

class AliSDK
{
    // mobile wap pay
    public static function wapPay(
        $partnerId,
        $priKeyPath,
        $outTradeNo,
        $subject, // 订单名称
        $totalAmount, // 订单支付金额
        $notifyUrl,
        $reteurnUrl
    ) {
        $parameter = array(
            'service' => 'alipay.wap.create.direct.pay.by.user',
            'partner' => $partnerId,
            'seller_id' => $partnerId, // 收款支付宝账号，一般情况下收款账号就是签约账号
            'payment_type'  => '1', // 支付类型。仅支持：1（商品购买）
            'notify_url'    => $notifyUrl,
            'return_url'    => $reteurnUrl,
            'out_trade_no'  => $outTradeNo,
            'subject'   => $subject,
            'total_fee' => $totalAmount,
            'show_url'  => '', // 商品展示网址
            'body'      => '', // 商品描述
            'it_b_pay'  => '1d', // 设置未付款交易的超时时间
            '_input_charset' => 'utf-8',
        );

        $kparams = self::makeSignParams($parameter);
        $parameter['sign'] = self::makeSign($kparams, $priKeyPath);
        $parameter['sign_type'] = 'RSA';

        $sHtml = '<form id="alipaysubmit" name="alipaysubmit"'
            . ' action="https://mapi.alipay.com/gateway.do?_input_charset=utf-8" method="get">';
        foreach ($parameter as $k => $v) {
            $sHtml .= '<input type="hidden" name="' . $k . '" value="' . $v .'"/>';
        }

        $sHtml .= '<input type="submit" value="确认"></form>';
        $sHtml .= '<script>document.forms["alipaysubmit"].submit();</script>';
        return $sHtml;
    }

    public static function verifyNotify(
        $partnerId,
        $cacertPath,
        $notifyId
    ) {
        $veryfyUrl = 'http://notify.alipay.com/trade/notify_query.do?';
        $veryfyUrl = $veryfyUrl . 'partner=' . $partnerId . '&notify_id=' . $notifyId;
        $responseTxt = self::cacertRequest($veryfyUrl, $cacertPath, 5);
        if (preg_match('/true$/i', $responseTxt) == 0) {
            return false;
        }
        return true;
    }

    public static function verifySign($params, $pubKeyPath, $sign)
    {
        $kparams = self::makeSignParams($params);
        $pubKey = file_get_contents($pubKeyPath);
        $res = openssl_get_publickey($pubKey);
        $result = openssl_verify($kparams, base64_decode($sign), $res);
        openssl_free_key($res);
        return $result == 1;
    }

    public static function makeSignParams($params)
    {
        ksort($params);
        $kparams = '';
        foreach ($params as $k => $v) {
            if ($k != 'sign' && $k != 'sign_type' && $v != '') {
                $kparams .= $k . '=' . $v . '&';
            }
        }
        $kparams = substr($kparams, 0, -1);
        if (get_magic_quotes_gpc()) {
            $kparams = stripslashes($kparams);
        }
        return $kparams;
    }

    public static function makeSign($params, $priKeyPath)
    {
        $priKey = file_get_contents($priKeyPath);
        $res = openssl_get_privatekey($priKey);
        $outSign = '';
        openssl_sign($params, $outSign, $res);
        openssl_free_key($res);
        return base64_encode($outSign);
    }

    public static function cacertRequest($url, $cacertPath, $timeout)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, false); // 过滤HTTP头
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 显示输出结果
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true); //SSL证书认证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); //严格认证
        curl_setopt($curl, CURLOPT_CAINFO, $cacertPath); //证书地址
        $ret = curl_exec($curl);
        curl_close($curl);
        return $ret;
    }
}

