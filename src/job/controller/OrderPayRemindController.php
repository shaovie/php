<?php
/**
 * @Author shaowei
 * @Date   2016-01-10
 */

namespace src\job\controller;

use \src\common\Nosql;
use \src\common\Log;
use \src\job\model\AsyncModel;
use \src\user\model\UserOrderModel;
use \src\user\model\WxUserModel;

class OrderPayRemindController extends JobController
{
    protected function run($idx) { }

    public function remind()
    {
        $nk = Nosql::NK_ASYNC_ORDER_PAY_REMIND_QUEUE;
        $beginTime = time();

        do {
            $now = time();
            do {
                $rawMsg = Nosql::lPop($nk);
                if ($rawMsg === false
                    || !isset($rawMsg[0])) {
                    break;
                }
                $data = json_decode($rawMsg, true);
                if ($now - $data['ctime'] > 600) {
                    $this->doRemind($data);
                } else {
                    Nosql::lPush($nk, $rawMsg);
                }
            } while (true);

            if ($now - $beginTime > 30) { // 30秒脚本重新执行一次
                break;
            }
            sleep(1);
        } while (true);
    }

    private function doRemind($data)
    {
        $orderId = $data['orderId'];
        $orderInfo = UserOrderModel::findOrderByOrderId($orderId);
        if (empty($orderInfo)) {
            return ;
        }
        if ($orderInfo['pay_state'] != PayModel::PAY_ST_UNPAY) {
            return ;
        }
        if ($orderInfo['order_env'] == UserOrderModel::ORDER_ENV_WEIXIN) {
            $this->doRemindInWeinXin($orderInfo);
        }
    }

    private function doRemindInWeinXin($orderInfo)
    {
        $wxUserInfo = WxUserModel::findUserByUserId($orderInfo['user_id']);
        if (empty($wxUserInfo['openid'])) {
            return ;
        }
        if (time() - $wxUserInfo['atime'] < 48*3600) {
            $content = '亲，您有一个未支付订单，请您及时付款以免过期\n\n'
                . '<a href=\"' . APP_URL_BASE. '/User/MyOrder/toPay' // TODO
                . '\">'
                . '前往支付>>'
                . '</a>';
            AsyncModel::asyncSendKfMsg(
                $wxUserInfo['openid'],
                'text',
                $content,
                0
            );
        }
    }
}

