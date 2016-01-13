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
use \src\user\model\UserModel;

class LoginController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->module = 'user';
    }

    // view
    public function index()
    {
        $this->display('xx');
    }

    // api
    public function smsLogin()
    {
        $phone = $this->getParam('phone', '');
        $code = $this->getParam('code', '');
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
        $this->onLoginOk($userInfo);
        $this->ajaxReturn(0, '登陆成功', '/TODO');
    }

    public function passwdLogin()
    {
        $phone = $this->getParam('phone', '');
        $passwd = $this->getParam('passwd', '');
        if (Check::isPhone($phone)) {
            $this->ajaxReturn(ERR_PARAMS_ERROR, '您输入的手机号无效');
            return ;
        }
        if (Check::isPasswd($passwd)) {
            $this->ajaxReturn(ERR_PARAMS_ERROR, '密码格式不正确');
            return ;
        }

        $userInfo = UserModel::findUserByPhone($phone);
        if (empty($userInfo)) {
            $this->ajaxReturn(ERR_PARAMS_ERROR, '该手机号码未注册，请先注册~');
            return ;
        }
        if ($userInfo['passwd'] != md5($passwd)) {
            $this->ajaxReturn(ERR_PASSWD_ERROR, '您输入的密码不正确，请重新输入');
            return ;
        }
        $this->onLoginOk($userInfo);
        $this->ajaxReturn(0, '登陆成功', '/TODO');
    }

    private function onLoginOk($userInfo)
    {
        $wxUserInfo = WxUserModel::findUserByUserId($userInfo['id']);
        $openid = '';
        if (!empty($wxUserInfo)) {
            $openid = $wxUserInfo['openid'];
        }
        UserModel::onLoginOk($userInfo['id'], $openid);
    }
}

