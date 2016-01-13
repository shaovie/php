<?php
/**
 * @Author shaowei
 * @Date   2015-12-03
 */

namespace src\mall\controller;

use \src\common\BaseController;
use \src\common\Util;
use \src\common\Check;
use \src\common\SMS;
use \src\common\Nosql;

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
        if (!Check::isPhone($phone)) {
            $this->ajaxReturn(ERR_PARAMS_ERROR, '请输入有效的手机号码！');
            return ;
        }
        $nk = Nosql::NK_REG_SMS_CODE . $phone;
        $ret = Nosql::get($nk);
        if (!empty($reg)
            && (CURRENT_TIME - (int)$ret) < 60) {
            $this->ajaxReturn(ERR_OPT_FREQ_LIMIT, '请不要频繁获取验证码');
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

        if (Check::isPhone($phone)) {
            $this->ajaxReturn(ERR_PARAMS_ERROR, '您输入的手机号无效');
            return ;
        }
        if (Check::isVerifyCode($code)) {
            $this->ajaxReturn(ERR_PARAMS_ERROR, '验证码无效');
            return ;
        }
        $nk = Nosql::NK_REG_SMS_CODE . $phone;
        $ret = Nosql::get($nk);
        if (empty($ret) || $ret != $code) {
            $this->ajaxReturn(ERR_PARAMS_ERROR, '验证码错误，请重新输入');
            return ;
        }
        Nosql::del($nk);
        $userInfo = UserModel::findUserByPhone($phone);
        if (empty($userInfo)) {
            $this->ajaxReturn(ERR_PARAMS_ERROR, '该手机号码未注册，请先注册~');
            return ;
        }

        $nickname = UserModel::getRandomNickname('wx');
        $passwd = '';
        $sex = 0;
        $headimgurl = '';
        $ret = UserModel::newOne(
            $phone,
            $passwd,
            $nickname,
            $sex,
            $headimgurl,
            UserModel::USER_ST_DEFAULT
        );
        if (!$ret) {
            $this->ajaxReturn(ERR_PARAMS_ERROR, '注册失败-系统出现异常，请稍后重试');
            return ;
        }
        $userInfo = UserModel::findUserByPhone($phone);
        if (empty($userInfo)) {
            $this->ajaxReturn(ERR_PARAMS_ERROR, '注册失败-系统出现异常，请稍后重试');
            return ;
        }
        UserModel::onLoginOk($userInfo['id'], ''); // TODO 是不是会自动绑定微信？
        $this->ajaxReturn(0, '登录成功', '/TODO');
    }
}

