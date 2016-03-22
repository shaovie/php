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

    public function wxJsApiPay($orderId, $orderDesc, $totalAmount)
    {
        $openInfo = WxSDK::getOpenInfo('snsapi_base', WX_PAY_APP_ID, WX_PAY_APP_SECRET);
        if (empty($openInfo['openid'])) {
            $this->ajaxReturn(ERR_SYSTEM_ERROR, '向微信请求支付信息失败，稍重试');
            return ;
        }
        $openid = $openInfo['openid'];

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
            $orderId,
            $orderDesc,
            ceil($totalAmount * 100), // 防止超过2位小数
            Util::getIp(),
            APP_URL_BASE . '/pay/PayNotify/wxUnified'
        );
        if ($jsApiParameters === false) {
            $this->ajaxReturn(ERR_SYSTEM_ERROR, '向微信申请支付失败，稍重试');
            return ;
        }

        $this->ajaxReturn(0, '', '', array('jsapiparams' => $jsApiParameters));
    }

    // 微信支付完成返回的页面
    protected function wxPayReturn()
    {
        $orderId = $this->getParam('orderId', '');
        if (empty($orderId)) {
            // ;
        }

        // 微信支付，看该用户是否关注过
        $nk  = Nosql::NK_WX_UNIFIED_PAY_UNSUBSCRIBE . $orderId;
        $ret = Nosql::get($nk);

        $this->display('wxpay_return');
    }

    protected function aliPay($orderId, $orderDesc, $totalAmount)
    {
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
            $orderDesc,
            number_format($totalAmount, 2, '.', ''), // 防止超过2位小数
            APP_URL_BASE . '/pay/PayNotify/aliPay', // notify url
            APP_URL_BASE . '/pay/OrderPay/aliPayReturn' // return url
        );
        if ($ret === false) {
            $this->ajaxReturn(ERR_SYSTEM_ERROR, '生成支付宝支付数据失败，稍重试');
            return ;
        }

        $payHtml = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 '
            . 'Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'
            . '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">'
            . '<title>支付宝安全支付</title>'
            . '</head><body>'
            . $ret . '</body></html>';
        echo $payHtml;
    }

    // 支付宝支付完成返回的页面
    public function aliPayReturn()
    {
        Log::pay('ali wap pay return raw data: ' . json_encode($_GET, JSON_UNESCAPED_UNICODE));

        $orderId = $this->getParam('orderId', '');
        if (empty($orderId)) {
            // ;
        }
        $this->display('alipay_return');
    }
}

