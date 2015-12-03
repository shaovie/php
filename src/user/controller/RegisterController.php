<?php
/**
 * @Author shaowei
 * @Date   2015-12-03
 */

namespace src\mall\controller;

use \src\common\BaseController;

class RegisterController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->module = 'user';
    }

    public function index()
    {
        $this->display('xx');
    }

    public function regSmsCode()
    {
        $phone = $this->getParam('phone', '');
        if (!Util::isPhone($phone)) {
            $this->ajaxReturn(1, '请输入有效的手机号码！');
            return ;
        }
        $nk = Nosql::NK_REG_SMS_CODE . $phone;
        $ret = Nosql::get($nk);
        if (!empty($reg)
            && (CURRENT_TIME - (int)$ret) < 60) {
            $this->ajaxReturn(1, '请不要频繁获取验证码');
            return ;
        }

        Nosql::setex($nk, Nosql::NK_REG_SMS_CODE_EXPIRE, (string)CURRENT_TIME);

        $code = SMS::genVerifyCode();
        SMS::verifyCode($phone, $code);
        $this->ajaxReturn(0, '');
    }

    public function register()
    {
        $phone = $this->postParam('phone', '');
        $code  = $this->postParam('code',  '');
    }
}

