<?php
/**
 * @Author shaowei
 * @Date   2015-07-20
 */

namespace src\common;

class WxSDK
{
    //= public methods
    public static function getSignPackage($url = '')
    {
        $jsApiTicket = self::getJsApiTicket();
        if (empty($url)) {
            $protocol = (!empty($_SERVER['HTTPS'])
                && $_SERVER['HTTPS'] !== 'off'
                || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
            $url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        }
        $nonceStr = Util::getRandomStr(16);

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = 'jsapi_ticket=' . $jsApiTicket
            . '&noncestr=' . $nonceNtr
            . '&timestamp=' . CURRENT_TIME
            . '&url=' . $url;
        $signature = sha1($string);
        $signPackage = array(
            'appId'     => WX_APP_ID,
            'nonceStr'  => $nonceStr,
            'timestamp' => CURRENT_TIME,
            'url'       => $url,
            'signature' => $signature,
            'rawString' => $string
        );
        return $signPackage; 
    }

    //获取授权信息
    public static function getOauthInfo($code, $appid, $appsecret)
    {
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='
            . $appid . '&secret=' . $appsecret 
            . '&code=' . $code . '&grant_type=authorization_code';
        $ret = HttpUtil::request($url);
        if ($ret === false) {
            return array();
        }
        return json_decode($ret, true);
    }

    // 静默登录非授权 获取用户 返回用户数据Array
    public static function getUserInfo($openid, $scope, $accessToken)
    {
        if ($scope == 'snsapi_base') {
            $accessToken = self::getAccessToken();
            $url = 'https://api.weixin.qq.com/cgi-bin/user/info?'
                . 'access_token=' . $accessToken . '&openid=' . $openid . '&lang=zh_CN';
        } else if ($scope == 'snsapi_userinfo') {
            $url = 'https://api.weixin.qq.com/sns/userinfo?'
                . 'access_token=' . $accessToken . '&openid=' . $openid . '&lang=zh_CN';
        } else {
            return array();
        }
        $ret = HttpUtil::request($url);
        if ($ret === false) {
            return array();
        }
        return json_decode($ret, true);
    }

    // 返回array('openid' => 'xx', 'access_token' => 'xxx', 'scope' => 'xxx')
    public static function getOpenInfo($scope, $appid, $appsecret)
    {
        if ($scope != 'snsapi_base'
            && $scope != 'snsapi_userinfo') {
            return array();
        }
        if (!isset($_GET['code'])) {
            $callbackUrl = 'http://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $params = array(
                'appid' => $appid,
                'redirect_uri' => $callbackUrl,
                'response_type' => 'code',
                'scope'=> $scope,
                'state' => '',
            );
            $params = http_build_query($params);
            $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?' . $params . '#wechat_redirect'; 
            header('Location: ' . $url);
            exit();
        }
        
        return self::getOauthInfo($_GET['code'], $appid, $appsecret);
    }

    public static function getUserList($nextOpenId)
    {
        $accessToken = self::getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/user/get?'
            . 'access_token=' . $accessToken;
        if (!empty($nextOpenId)) {
            $url .= '&next_openid=' . $nextOpenId;
        }
        $data = HttpUtil::request($url);
        if ($data === false) {
            return array();
        }
        return json_decode($data, true);
    }

    /**
     * 主动删除token信息
     * 通常在token失效的时候使用
     */
    public static function delAccessToken()
    {
        Cache::del(Cache::CK_WX_ACCESS_TOKEN . WX_APP_ID);
        Log::warng('weixin - user del access token!');
    }

    // 生成情景二维码 $tmp:true 临时二维码  $tmp:false 永久二维码
    public static function createSceneQRcode($sceneId, $tmp = true, $expire = 604800)
    {
        $ck = Cache::CK_WX_SCENE_QRCODE . $sceneId;
        $result = Cache::get($ck);
        if ($result !== false) {
            return $result;
        }
        $cacheExpire = Cache::CK_WX_SCENE_QRCODE_EXPIRE;
        if ($expire < Cache::CK_WX_SCENE_QRCODE_EXPIRE) {
            $cacheExpire = $expire;
        }
        $accessToken = self::getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $accessToken;
        $data = '{"expire_seconds":' . $expire
            . ',"action_name":"' . ($tmp ? 'QR_SCENE' : 'QR_LIMIT_SCENE') . '"'
            . ',"action_info":{"scene":{"scene_id":' . $sceneId . '}}';
        $ret = HttpUtil::request($url, $data, array('Content-Type: application/json'));
        if ($ret === false) {
            return false;
        }
        $ret = json_decode($ret, true);
        if (isset($ret['errcode']) && $ret['errcode'] != 0) {
            Log::warng('weixin - scene qr-code error: ' . $ret['errmsg'] . ' sceneid=' . $sceneId);
            return false;
        }
        $ret = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . $ret['ticket'];
        Cache::setex($ck, $cacheExpire, $ret);
        return $ret;
    }

    // long url to short url
    // return original url on failed!
    public static function shortUrl($origUrl)
    {
        if (empty($origUrl)) {
            return $origUrl;
        }
        $ck = Cache::CK_SHORT_URL . $origUrl;
        $result = Cache::get($ck);
        if ($result !== false) {
            return $result;
        }

        $accessToken = self::getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/shorturl?access_token=' . $accessToken;
        $data = '{"action":"long2short","long_url":"' . $origUrl . '"}';
        $ret = HttpUtil::request($url, $data, array('Content-Type: application/json'));
        if ($ret === false) {
            return $origUrl;
        }
        $ret = json_decode($ret, true);
        if (!empty($ret['errcode'])) {
            Log::warng('weixin - short url error: ' . $ret['errmsg'] . ' url=' . $origUrl);
            return $origUrl;
        }

        Cache::set($ck, $ret['short_url']);
        return $ret['short_url'];
    }

    // send kefu message
    public static function sendKfTextMsg($openid, $msg)
    {
        $accessToken = self::getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $accessToken;
        $msg = '{"touser":"' . $openid . '",'
            . '"msgtype":"text",'
            . '"text":{"content":"' . $msg . '"}';
        $ret = HttpUtil::request($url, $msg, array('Content-Type: application/json'));
        if ($ret === false) {
            return false;
        }
        $ret = json_decode($ret, true);
        if (!empty($ret['errcode'])) {
            Log::warng('weixin - kf text msg error: ' . $ret['errmsg'] . ' msg=' . $msg);
            return false;
        }
        return true;
    }

    public static function sendKfNewsMsg($openid, $articles)
    {
        $accessToken = self::getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $accessToken;
        $msg = array(
            'touser'  => $openid,
            'msgtype' => 'news',
            'news'    => array('articles' => $articles)
        );
        $msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
        $ret = HttpUtil::request($url, $msg, array('Content-Type: application/json'));
        if ($ret === false) {
            return false;
        }
        $ret = json_decode($ret, true);
        if (!empty($ret['errcode'])) {
            Log::warng('weixin - kf news msg error: ' . $ret['errmsg'] . ' msg=' . $msg);
            return false;
        }
        return true;
    }

    // send template message
    public static function sendTplMsg($msg/*raw json*/)
    {
        $accessToken = self::getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $accessToken;
        $ret = HttpUtil::request($url, $msg, array('Content-Type: application/json'));
        if ($ret === false) {
            return false;
        }
        $ret = json_decode($ret, true);
        if (!empty($ret['errcode'])) {
            Log::warng('weixin - tpl msg error: ' . $ret['errmsg'] . ' msg=' . $msg);
            return false;
        }
        return true;
    }

    public static function createMenu($menu /*raw json*/)
    {
        $accessToken = self::getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=' . $accessToken;
        $ret = HttpUtil::request($url, $menu, array('Content-Type: application/json'));
        if ($ret === false) {
            return false;
        }
        $ret = json_decode($ret, true);
        if (!empty($ret['errcode'])) {
            Log::warng('weixin - create menu error: ' . $ret['errmsg'] . ' msg=' . $msg);
            return false;
        }
        return true;
    }

    // https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_1
    public static function jsApiPay(
        $mchid,
        $appId,
        $signKey,
        $openid,
        $outTradeNo,
        $body, // 商品或支付单简要描述
        $totalAmount, // 订单总金额（分）
        $clientIp, // 用户端ip
        $notifyUrl,
        $attach // String(127) 附加数据
    ) {
        if (empty($mchid)
            || empty($appId)
            || empty($signKey)
            || empty($outTradeNo)
            || empty($body)
            || empty($totalAmount)
            || empty($clientIp)
            || empty($notifyUrl)) {
            Log::pay('jsapipay error - params error! ' . json_encode(func_get_args()));
            return false;
        }

        $data = array(
            'appid' => $appId,
            'attach' => $attach,
            'body' => $body,
            'mch_id' => $mchid,
            'nonce_str' => Util::getRandomStr(32),
            'notify_url' => $notifyUrl,
            'openid' => $openid,
            'out_trade_no' => $outTradeNo,
            'spbill_create_ip' => $clientIp,
            'total_fee' => $totalAmount,
            'trade_type' => 'JSAPI',
        );

        $data['sign'] = self::sign($data, $signKey);

        $postXml = Util::arrayToXml($data);

        $responseXml = HttpUtil::request(
            'https://api.mch.weixin.qq.com/pay/unifiedorder',
            $postXml,
            false,
            5);
        if ($responseXml === false) {
            Log::pay('jsapipay error - request fail or timeout! '
                . json_encode(func_get_args(), JSON_UNESCAPED_UNICODE));
            return false;
        }
        libxml_disable_entity_loader(true);
        $ret = simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $ret = json_decode(json_encode($ret), true);
        if ($ret['return_code'] != 'SUCCESS') {
            Log::pay('jsapipay error - return_code FAIL, err='
                . json_encode($ret, JSON_UNESCAPED_UNICODE) . ' data='
                . json_encode(func_get_args(), JSON_UNESCAPED_UNICODE));
            return false;
        }
        if ($ret['result_code'] != 'SUCCESS') {
            Log::pay('jsapipay error - result_code FAIL, err='
                . json_encode($ret, JSON_UNESCAPED_UNICODE) . ' data='
                . json_encode(func_get_args(), JSON_UNESCAPED_UNICODE));
            return false;
        }

        // unifiedorder ok , then create jsapi parameters
        $data = array(
            'appId' => $appId,
            'nonceStr' => Util::getRandomStr(32),
            'timeStamp' => CURRENT_TIME,
            'package' => 'prepay_id=' . $ret['prepay_id'],
            'signType' => 'MD5',
        );
        $data['paySign'] = self::sign($data, $signKey);
        return json_encode($data);
    }

    // https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_4
    public static function refund(
        $mchid,
        $appId,
        $signKey,
        $openid,
        $outTradeNo,
        $outRefundNo,
        $totalAmount,  // 订单总金额（分）
        $refundAmount  // 退款总金额（分）
    ) {
        if (empty($mchid)
            || empty($appId)
            || empty($signKey)
            || empty($openid)
            || empty($outTradeNo)
            || empty($outRefundNo)
            || empty($totalAmount)
            || empty($refundAmount)) {
            Log::pay('jsapipay error - params error! ' . json_encode(func_get_args()));
            return false;
        }

        $data = array(
            'appid' => $appId,
            'mch_id' => $mchid,
            'nonce_str' => Util::getRandomStr(32),
            'openid' => $openid,
            'out_trade_no' => $outTradeNo,
            'out_refund_no' => $outRefundNo,
            'total_fee' => $totalAmount,
            'refund_fee' => $refundAmount,
            'op_user_id' => $mchid,
        );

        $data['sign'] = self::sign($data, $signKey);

        $postXml = Util::arrayToXml($data);

        $responseXml = HttpUtil::request(
            'https://api.mch.weixin.qq.com/secapi/pay/refund',
            $postXml,
            false,
            5);
        if ($responseXml === false) {
            Log::pay('weixin refund error - request fail or timeout! '
                . json_encode(func_get_args(), JSON_UNESCAPED_UNICODE));
            return false;
        }
        libxml_disable_entity_loader(true);
        $ret = simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $ret = json_decode(json_encode($ret), true);
        if ($ret['return_code'] != 'SUCCESS') {
            Log::pay('weixin refund error - return_code FAIL, err='
                . json_encode($ret, JSON_UNESCAPED_UNICODE) . ' data='
                . json_encode(func_get_args(), JSON_UNESCAPED_UNICODE));
            return false;
        }
        if ($ret['result_code'] != 'SUCCESS') {
            Log::pay('weixin refund error - result_code FAIL, err='
                . json_encode($ret, JSON_UNESCAPED_UNICODE) . ' data='
                . json_encode(func_get_args(), JSON_UNESCAPED_UNICODE));
            return false;
        }
        return $ret;
    }

    // 企业支付，发送现金红包
    // refer: https://pay.weixin.qq.com/wiki/doc/api/cash_coupon.php?chapter=13_5
    // return response object on success, return false on send fail.
    public static function sendCashRedPack(
        $mchid,
        $appId,
        $signKey,
        $nickName,
        $sendName,
        $openid,
        $amount,
        $wishing,
        $actName,
        $remark,
        $selfIp,
        $apiClientCertPem,
        $apiClientKeyPem,
        $rootcaPem
    ) {
        if (empty($mchid)
            || empty($appId)
            || empty($signKey)
            || empty($nickName)
            || empty($sendName)
            || empty($amount)
            || empty($openid)
            || empty($wishing)
            || empty($actName)
            || empty($selfIp)
            || empty($remark)
            || empty($apiClientCertPem)
            || empty($apiClientKeyPem)
            || empty($rootcaPem)
        ) {
            Log::error('wxpay cashredpack - params error! ' . json_encode(func_get_args()));
            return false;
        }

        $data = array(
            'nonce_str' => WxSDK::createNonceStr(32),
            'mch_billno' => $mchid . date('Ymd') . mt_rand(1000000000, 1999999999),
            'mch_id' => $mchid,
            'wxappid' => $appId,
            'nick_name' => $nickName,
            'send_name' => $sendName,
            're_openid' => $openid,
            'total_amount' => $amount,
            'total_num' => 1,
            'wishing' => $wishing,
            'client_ip' => $selfIp,
            'act_name' => $actName,
            'remark' => $remark
        );

        $data['sign'] = self::sign($data, $signKey);

        $postXml = self::arrayToXml($data, true);

        $responseXml = self::postSSL(
            'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack',
            $postXml,
            $apiClientCertPem,
            $apiClientKeyPem,
            $rootcaPem
        );
        if ($responseXml === false) {
            return false;
        }
        libxml_disable_entity_loader(true);
        $ret = simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $ret = json_decode(json_encode($ret), true);
        if ($ret['return_code'] != 'SUCCESS') {
            Log::pay('weixin cashredpack error - return_code FAIL, err='
                . json_encode($ret, JSON_UNESCAPED_UNICODE) . ' data='
                . json_encode(func_get_args(), JSON_UNESCAPED_UNICODE));
            return false;
        }
        if ($ret['result_code'] != 'SUCCESS') {
            Log::pay('weixin cashredpack error - result_code FAIL, err='
                . json_encode($ret, JSON_UNESCAPED_UNICODE) . ' data='
                . json_encode(func_get_args(), JSON_UNESCAPED_UNICODE));
            if ($ret['err_code'] == 'NOTENOUGH') {
                // TODO
            }
            return false;
        }
        return $ret;
    }

    // 企业支付，支付到用户余额
    // refer: https://pay.weixin.qq.com/wiki/doc/api/mch_pay.php?chapter=14_2
    // return response object on success, return false on send fail.
    public static function payToChange(
        $mchid,
        $appId,
        $signKey,
        $openid,
        $amount,
        $desc,
        $selfIp,
        $apiClientCertPem,
        $apiClientKeyPem,
        $rootcaPem
    ) {
        if (empty($mchid)
            || empty($appId)
            || empty($signKey)
            || empty($openid)
            || empty($amount)
            || empty($desc)
            || empty($selfIp)
            || empty($apiClientCertPem)
            || empty($apiClientKeyPem)
            || empty($rootcaPem)
        ) {
            Log::error('wxpay tochange - params error! ' . json_encode(func_get_args()));
            return false;
        }

        $data = array(
            'nonce_str' => Util::getRandomStr(32),
            'mch_appid' => $appId,
            'mchid' => $mchid,
            'partner_trade_no' => $mchid . date('Ymd') . mt_rand(1000000000, 1999999999),
            'check_name' => 'NO_CHECK',
            'openid' => $openid,
            'amount' => $amount,
            'spbill_create_ip' => $selfIp,
            'desc' => $desc
        );

        $data['sign'] = self::sign($data, $signKey);

        $postXml = self::arrayToXml($data, false);

        $responseXml = Util::postSSL(
            'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers',
            $postXml,
            $apiClientCertPem,
            $apiClientKeyPem,
            $rootcaPem
        );
        if ($responseXml === false) {
            return false;
        }
        libxml_disable_entity_loader(true);
        $ret = simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $ret = json_decode(json_encode($ret), true);
        if ($ret['return_code'] != 'SUCCESS') {
            Log::pay('wxpay tochange error - return_code FAIL, err='
                . json_encode($ret, JSON_UNESCAPED_UNICODE) . ' data='
                . json_encode(func_get_args(), JSON_UNESCAPED_UNICODE));
            return false;
        }
        if ($ret['result_code'] != 'SUCCESS') {
            Log::pay('wxpay tochange error - result_code FAIL, err='
                . json_encode($ret, JSON_UNESCAPED_UNICODE) . ' data='
                . json_encode(func_get_args(), JSON_UNESCAPED_UNICODE));
            if ($ret['err_code'] == 'NOTENOUGH') {
                // TODO
            }
            return false;
        }
        return $ret;
    }

    public static function sign($data, $signKey)
    {
        ksort($data);
        $kstring = '';
        foreach ($data as $k => $v) {
            if ($k != 'sign' && $v != '' && !is_array($v)) {
                $kstring .= $k . '=' . $v . '&';
            }
        }
        $kstring .= 'key=' . $signKey;
        return strtoupper(md5($kstring));
    }

    //= private method
    //
    private static function getAccessToken()
    {
        $ck = Cache::CK_WX_ACCESS_TOKEN . WX_APP_ID;
        $accessToken = Cache::get($ck);
        if (!empty($accessToken)) {
            return $accessToken;
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/token?'
            . 'grant_type=client_credential'
            . '&appid=' . WX_APP_ID
            . '&secret=' . WX_APP_SECRET;
        $ret = HttpUtil::request($url);
        if ($ret === false) {
            return false;
        }
        $ret = json_decode($ret, true);
        if (!empty($ret['errcode'])) {
            Log::fatal('weixin - get access token failed! errcode = ' . $ret['errcode']
                . ' errmsg=' . $ret['errmsg']);
            return false;
        }
        $accessToken = $ret['access_token'];
        $expireIn = (int)$ret['expires_in'];
        if (empty($accessToken)) {
            Log::fatal('weixin - get access token empty!');
            return false;
        }
        Cache::setex($ck, $expireIn - 300, $accessToken);
        return $accessToken;
    }

    private static function getJsApiTicket()
    {
        $ck = Cache::CK_WX_JSAPI_TICKET . WX_APP_ID;
        $ret = Cache::get($ck);
        if (!empty($ret)) {
            return $ret;
        }
        $accessToken = self::getAccessToken();
        if ($accessToken === false) {
            return false;
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsApi&access_token=' . $accessToken;
        $ret = HttpUtil::request($url);
        if ($ret === false) {
            Log::fatal('weixin - get js api ticket failed!');
            return false;
        }
        $ret = json_decode($ret, true);
        if (!empty($ret['errcode'])) {
            Log::fatal('weixin - get js api ticket failed! errcode = ' . $ret['errcode']
                . ' errmsg=' . $ret['errmsg']);
            return false;
        }
        $ticket = $ret['ticket'];
        $expireIn = (int)$ret['expires_in'];
        if (empty($ticket)) {
            Log::fatal('weixin - get js api ticket empty!');
            return false;
        }
        Cache::setex($ck, $expireIn - 300, $ticket);
        return $ticket;
    }
}

