<?php
/**
 * @Author shaowei
 * @Date   2015-12-23
 */

namespace src\api\controller;

use \src\common\Nosql;
use \src\common\BaseController;

class OrderController extends BaseController
{
    public function getOrderState()
    {
        $token = $this->getParam('token', '');
        if (empty($token)) {
            $this->ajaxReturn(ERR_PARAMS_ERROR, '参数错误');
            return ;
        }

        $ret = Nosql::get($nk);
        if (empty($ret)) {
            $this->ajaxReturn(ERR_OPT_FAIL, '');
            return ;
        }
        $ret = json_decode($ret, true);
        $this->ajaxReturn($ret['code'], $ret['desc'], '', $ret['result']);
    }
}

