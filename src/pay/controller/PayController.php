<?php
/**
 * @Author shaowei
 * @Date   2015-09-17
 */

namespace src\pay\controller;

use \src\common\BaseController;
use \src\common\WxSDK;
use \src\common\AliSDK;
use \src\common\Nosql;
use \src\common\Util;
use \src\common\Log;
use \src\pay\model\PayModel;

class PayController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->module = 'pay';
    }

    public function wxJsApiPay()
    {
        $openInfo = WxSDK::getOpenInfo('snsapi_base', WX_PAY_APP_ID, WX_PAY_APP_SECRET);
        if (empty($openInfo['openid'])) {
            echo '向微信请求支付失败，稍重试';
            return ;
        }
        $openid = $openInfo['openid'];
        $orderId = date('Ymd') . mt_rand(1000000000, 1999999999);

        PayModel::onCreateOrderOk(
            $orderId,
            array(
                'pay_type' => PayModel::PAY_TYPE_WX,
            )
        );

        $jsApiParameters = WxSDK::jsApiPay(
            WX_PAY_MCHID,
            WX_PAY_APP_ID,
            WX_PAY_KEY,
            $openid,
            $orderId, // order id
            '测试1分钱商品',
            1,
            Util::getIp(),
            APP_URL_BASE . '/pay/PayNotify/wxUnified'
        );
        if ($jsApiParameters === false) {
            echo '获取js参数失败';
            exit();
        }

        echo <<<HTML
            <html>
            <head>
            <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
            <meta name="viewport" content="width=device-width, initial-scale=1"/> 
            <title>微信支付样例-支付</title>
            <script type="text/javascript">
            //调用微信JS api 支付
            function jsApiCall()
            {
                WeixinJSBridge.invoke(
                    'getBrandWCPayRequest',
                    $jsApiParameters,
                    function(res){
                        WeixinJSBridge.log(res.err_msg);
                        if (res.err_msg == "get_brand_wcpay_request:ok") {
                            setTimeout(function() {
                                location.href = "http://host/pay/OrderPay/wxPayReturn";
                                }, 500);
                        } else {
                            if (res.err_msg != "get_brand_wcpay_request:cancel") {
                                alert(res.err_code+res.err_desc+res.err_msg);
                            }
                        }
                     }
                );
            }

        function callpay()
        {
            if (typeof WeixinJSBridge == "undefined"){
                if( document.addEventListener ){
                    document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
                }else if (document.attachEvent){
                    document.attachEvent('WeixinJSBridgeReady', jsApiCall); 
                    document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
                }
            }else{
                jsApiCall();
            }
        }
        </script>
            </head>
            <body>
            <br/>
            <font color="#9ACD32"><b>该笔订单支付金额为<span style="color:#f00;font-size:50px">1分</span>钱</b></font><br/><br/>
            <div align="center">
            <button style="width:210px; height:50px; border-radius: 15px;background-color:#FE6714; border:0px #FE6714 solid; cursor: pointer;  color:white;  font-size:16px;" type="button" onclick="callpay()" >立即支付</button>
            </div>
            </body>
            </html>
HTML;
    }

    public function wxPayReturn()
    {
        echo 'xxxxx';
        $orderId = $this->getParam('orderId', '');
        if (empty($orderId)) {
            // ;
        }

        // 微信支付，看该用户是否关注过
        $nk  = Nosql::NK_WX_UNIFIED_PAY_UNSUBSCRIBE . $orderId;
        $ret = Nosql::get($nk);
    }

    public function aliPay()
    {
        $orderId = date('Ymd') . mt_rand(1000000000, 1999999999);

        PayModel::onCreateOrderOk(
            $orderId,
            array(
                'pay_type' => PayModel::PAY_TYPE_ALI,
            )
        );

        $ret = AliSDK::wapPay(
            ALI_PAY_PARTNER_ID,
            CONFIG_PATH . '/alipay/rsa_private_key.pem',
            $orderId,
            '测试1分钱',
            0.01,
            APP_URL_BASE . '/pay/PayNotify/aliPay', // notify url
            APP_URL_BASE . '/pay/OrderPay/aliPayReturn' // return url
        );
        echo <<<HTML
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html>
            <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
            <title>支付宝手机网站支付接口接口</title>
            </head>
            $ret;
            </body>
            </html>
HTML;
    }
    public function aliPayReturn()
    {
        Log::pay('ali wap pay return raw data: ' . json_encode($_GET, JSON_UNESCAPED_UNICODE));
        echo "xxx";

        $orderId = $this->getParam('orderId', '');
        if (empty($orderId)) {
            // ;
        }
    }
}

