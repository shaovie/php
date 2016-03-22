<?php
/**
 * @Author shaowei
 * @Date   2015-12-01
 */

namespace src\common;

use \src\common\Util;
use \src\common\Nosql;
use \src\common\Session;
use \src\user\model\WxUserModel;
use \src\user\model\UserModel;

class UserBaseController extends BaseController
{
    protected $userInfo   = array();
    protected $wxUserInfo = array();

    public function __construct()
    {
        parent::__construct();
    }

    public function userId()
    {
        if (empty($this->userInfo)) {
            return 0;
        }
        return (int)$this->userInfo['id'];
    }

    public function doLogin()
    {
        $key = Session::getSid('user');
        $userInfo = Nosql::get(Nosql::NK_USER_SESSOIN . $key);
        if (!empty($userInfo)) {
            $userAgent = '';
            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $userAgent = $_SERVER['HTTP_USER_AGENT'];
            }
            $userInfo = json_decode($userInfo, true);
            if ($userInfo['userAgent'] != $userAgent) {
                return false;
            }
            if (Util::inWeixin()) {
                return $this->doLoginInWx($userInfo['openid']);
            } else {
                return $this->doLoginDefault($userInfo['userId']);
            }
            return false;
        }
        return -1;
    }

    public function autoLogin()
    {
        if ($this->doLogin() === -1) {
            $this->toLogin();
        }
    }

    public function toLogin()
    {
        if (Util::inWeixin()) {
            $openid = $this->toWxLogin();
            if ($openid !== false) {
                if ($this->doLoginInWx($openid) === true) {
                    UserModel::onLoginOk(0, $openid);
                } else {
                    //
                }
            } else {
                // 
            }
        } else {
            $this->toDefaultLogin();
        }
    }

    public function checkLoginAndNotice()
    {
        if (!$this->hadLogin()) {
            if ($this->isAjax()) {
                $this->ajaxReturn(ERR_NOT_LOGIN, '您未登录或未注册');
            } else {
                header('Location: /'); // TODO
            }
            exit();
        }
        return true;
    }

    public function hadLogin()
    {
        return $this->userId() > 0;
    }

    private function doLoginInWx($openid)
    {
        $this->wxUserInfo = WxUserModel::findUserByOpenId($openid);
        if (!empty($this->wxUserInfo)) {
            $this->userInfo = UserModel::findUserById($this->wxUserInfo['user_id']);
            return true;
        }
        return false;
    }

    private function doLoginDefault($userId)
    {
        $this->userInfo = UserModel::findUserById($userId);
        if (!empty($this->userInfo)) {
            return true;
        }
        return false;
    }

    private function toWxLogin()
    {
        $openInfo = WxSDK::getOpenInfo('snsapi_base', WX_APP_ID, WX_APP_SECRET);
        if (empty($openInfo['openid'])) {
            // TODO 这里要显示的告诉用户
            // header('Location: /TODO');
            // exit(0);
            return false;
        }
        $wxUserInfo = WxSDK::getUserInfo($openInfo['openid'], 'snsapi_base');
        if (empty($wxUserInfo)) { //
            Log::warng('first get wxuinfo:' . $openInfo['openid'] . ' fail when autologin');
            $wxUserInfo = WxSDK::getUserInfo($openInfo['openid'], 'snsapi_base');
            if (empty($wxUserInfo)) { //
                Log::warng('second get wxuinfo:' . $openInfo['openid'] . ' fail when autologin');
                // TODO 这里要显示的告诉用户
                // header('Location: /TODO');
                // exit(0);
                return false;
            }
        }
        $wxDBUserInfo = WxUserModel::findUserByOpenId($openInfo['openid']);
        if (empty($wxDBUserInfo)) { // new one
            $from = WxUserModel::SUBSCRIBE_FROM_ALREADY;
            if ((int)$wxUserInfo['subscribe'] == 0) {
                $from = WxUserModel::SUBSCRIBE_FROM_UNSUBSCRIBE;
            }
            $ret = WxUserModel::newOne($wxUserInfo, $from);
            if ($ret === false) {
                // TODO 这里要显示的告诉用户
                // header('Location: /TODO');
                // exit(0);
                return false;
            }
        }
        return $openInfo['openid'];
    }

    private function toDefaultLogin()
    {
        header('Location: /user/Login');
    }
}

