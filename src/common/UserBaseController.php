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

    public function autoLogin()
    {
        $key = Session::getSid('user');
        $userInfo = Nosql::get(Nosql::NK_USER_SESSOIN . $key);
        if (!empty($userInfo)) {
            $userAgent = '';
            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $userAgent = $_SERVER['HTTP_USER_AGENT'];
            }
            $userInfo = json_decode($userInfo, true);
            if ($userInfo['userAgent'] == $userAgent) {
                if (Util::inWx()) {
                    $this->doLoginInWx($userInfo['openid']);
                } else {
                    $this->doLoginDefault($userInfo['userId']);
                }
            }
        } else {
            $this->toLogin();
        }
    }

    public function toLogin()
    {
        if (Util::inWx()) {
            $this->toWxLogin();
        } else {
            $this->toDefaultLogin();
        }
    }

    public function hadLogin()
    {
        return empty($this->userInfo['id']);
    }

    private function doLoginInWx($openid)
    {
        $this->wxUserInfo = WxUserModel::findUserByOpenId($openid);
        if (!empty($this->wxUserInfo)) {
            UserModel::onLoginOk($this->wxUserInfo['user_id'], $openid);
            $this->userInfo = UserModel::findUserById($this->wxUserInfo['user_id']);
            return true;
        }
        return false;
    }

    private function doLoginDefault($userId)
    {
        $this->userInfo = UserModel::findUserById($userId);
        if (!empty($this->userInfo)) {
            UserModel::onLoginOk($this->userInfo['user_id'], '');
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
            return ;
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
                return ;
            }
        }
        $ret = WxUserModel::findUserByOpenId($openInfo['openid']);
        if (!empty($ret)) {
            $this->doLoginInWx($openInfo['openid']);
            return ;
        } else { // create one
            $from = WxUserModel::SUBSCRIBE_FROM_ALREADY;
            if ((int)$wxUserInfo['subscribe'] == 0) {
                $from = WxUserModel::SUBSCRIBE_FROM_UNSUBSCRIBE;
            }
            $ret = WxUserModel::newOne($wxUserInfo, $from);
            if ($ret === false) {
                // TODO 这里要显示的告诉用户
                // header('Location: /TODO');
                // exit(0);
                return ;
            }

            $ret = $this->doLoginInWx($openInfo['openid']);
            if ($ret === false) {
                Log::error("create wx user fail! " . json_encode($wxUserInfo, JSON_UNESCAPED_UNICODE));
                // TODO 这里要显示的告诉用户
                // header('Location: /TODO');
                // exit(0);
            }
        }
    }

    private function toDefaultLogin()
    {
        header('Location: /user/Login');
    }
}

