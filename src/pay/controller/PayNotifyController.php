<?php
/**
 * @Author shaowei
 * @Date   2015-09-17
 */

namespace src\pay\controller;

use \src\common\WxSDK;
use \src\common\Cache;
use \src\common\AliSDK;
use \src\common\Log;

class PayNotifyController extends PayController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function wxUnified()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            exit();
        }

        $data = file_get_contents('php://input');
        libxml_disable_entity_loader(true);
        $data = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
        $data = json_encode($data);
        Log::pay('wexin unified pay notify raw data: ' . $data);

        $data = json_decode($data, true);
        $sign = WxSDK::sign($data, WX_PAY_KEY);
        if ($data['sign'] != $sign) {
            Log::pay('wexin unified pay notify error : sign failed! ' . json_encode($data));
            echo '<xml><return_code>FAIL</return_code><return_msg>sign fail</return_msg></xml>';
            return ;
        }

        if ($data['return_code'] != 'SUCCESS') {
            Log::pay('wexin unified pay notify fail : ' . json_encode($data));
            echo '<xml><return_code>FAIL</return_code><return_msg>return fail</return_msg></xml>';
            return ;
        }
        if ($data['result_code'] != 'SUCCESS') {
            Log::pay('wexin unified pay notify fail : ' . json_encode($data));
            echo '<xml><return_code>FAIL</return_code><return_msg>result fail</return_msg></xml>';
            return ;
        }

        $transactionId = $data['transaction_id'];

        $ck = Nosql::NK_PAY_NOTIFY_DE_DUPLICATION . $data['out_trade_no'];
        $ck = Cache::get($ck);
        if (!empty($ck)) {
            echo '<xml><return_code>SUCCESS</return_code><return_msg>OK</return_msg></xml>';
            Log::pay('wexin unified pay notify ok(had handled) : ' . json_encode($data));
            return ;
        }

        if ($this->onPayNotifyOk(
                $data['out_trade_no'], // 商户订单号
                $data['total_fee'],    // 订单总金额
                $data['cash_fee']) === true) { // 订单现金支付金额
            Cache::setex($ck, Nosql::NK_PAY_NOTIFY_DE_DUPLICATION_EXPIRE, 'x');
            echo '<xml><return_code>SUCCESS</return_code><return_msg>OK</return_msg></xml>';
            Log::pay('wexin unified pay notify success : ' . json_encode($data));
            return ;
        }

        if ($data['is_subscribe'] == 'N'
            && $data['trade_type'] == 'JSAPI') {
            $this->onWxPayOkUnSubscribe($data['openid'], $data['out_trade_no']);
        }

        echo '<xml><return_code>FAIL</return_code><return_msg>handle fail</return_msg></xml>';
        Log::pay('wexin unified pay notify fail : ' . json_encode($data));
    }

    public function aliPay()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            exit();
        }

        if (!isset($_POST['sign'])
            || !isset($_POST['sign_type'])
            || $_POST['sign_type'] != 'RSA') {
            echo 'fail';
            exit();
        }

        $ret = AliSDK::verifySign(
            $_POST,
            CONFIG_PATH . '/alipay/alipay_public_key.pem',
            $_POST['sign']
        );
        $ret = true; // TODO
        if ($ret === false) {
            Log::pay('ali wap pay notify fail (sign fail) ' . json_encode($_POST, JSON_UNESCAPED_UNICODE));
            echo 'success';
            exit();
        }

        if (!empty($_POST['notify_id'])) {
            $ret = AliSDK::verifyNotify(ALI_PAY_PARTNER_ID,
                CONFIG_PATH . '/alipay/cacert.pem',
                $_POST['notify_id']
            );
            if ($ret === false) {
                Log::pay('ali wap pay notify fail (verify notify_id fail) '
                    . json_encode($_POST, JSON_UNESCAPED_UNICODE));
                echo 'fail';
                exit();
            }
        }

        $ck = Nosql::NK_PAY_NOTIFY_DE_DUPLICATION . $data['out_trade_no'];
        $ck = Cache::get($ck);
        if (!empty($ck)) {
            Log::pay('ali wap pay notify success (had handled): ' . json_encode($_POST, JSON_UNESCAPED_UNICODE));
            echo 'success';
            return ;
        }

        if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
            if ($this->onPayNotifyOk(
                    $_POST['out_trade_no'],
                    $_POST['total_fee'],
                    $_POST['total_fee']) === true) {
                Cache::setex($ck, Nosql::NK_PAY_NOTIFY_DE_DUPLICATION_EXPIRE, 'x');
                Log::pay('ali wap pay notify success : ' . json_encode($_POST, JSON_UNESCAPED_UNICODE));
                echo 'success';
                return ;
            }
        } else { // TRADE_FINISHED WAIT_BUYER_PAY 不处理
            echo 'success';
            return ;
        }
        echo 'fail';
    }

    //= private methods
    private function onPayNotifyOk($outTradeNo, $totalAmount, $payOkAmount)
    {
        return true;
    }

    // 微信支付后，发现用户未支付
    private function onWxPayOkUnSubscribe($openid, $outTradeNo)
    {
        $ck = Nosql::NK_WX_UNIFIED_PAY_UNSUBSCRIBE . $outTradeNo;
        Cache::setex($ck, Nosql::NK_WX_UNIFIED_PAY_UNSUBSCRIBE_EXPIRE, 'x');
    }
}

