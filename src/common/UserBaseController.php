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
                    return $this->doLoginInWx($userInfo['openid']);
                } else {
                    return $this->doLoginDefault($userInfo['userId']);
                }
            }
        }
        return false;
    }

    public function hadLogin()
    {
        return empty($this->userInfo['id']);
    }

    public function doLoginInWx($openid)
    {
        $this->wxUserInfo = WxUserModel::findUserByOpenId($openid);
        if (!empty($this->wxUserInfo)) {
            $this->userInfo = UserModel::findUserById($this->wxUserInfo['user_id']);
        }
        return empty($this->wxUserInfo);
    }

    public function doLoginDefault($userId)
    {
        $this->userInfo = UserModel::findUserById($userId);
        return empty($this->userInfo);
    }

    public function toLogin()
    {
        if (Util::inWx()) {
            $this->toWxLogin();
        } else {
            $this->toDefaultLogin();
        }
    }

    public function toWxLogin()
    {
        $openInfo = WxSDK::getOpenInfo('snsapi_base', WX_APP_ID, WX_APP_SECRET);
        if (empty($openInfo['openid'])) {
            return ;
        }
        $wxUserInfo = WxSDK::getUserInfo($openInfo['openid'], 'snsapi_base');
        if (empty($wxUserInfo)) { //
            Log::warng('first get wxuinfo:' . $openInfo['openid'] . ' fail when autologin');
            $wxUserInfo = WxSDK::getUserInfo($openInfo['openid'], 'snsapi_base');
            if (empty($wxUserInfo)) { //
                Log::warng('second get wxuinfo:' . $openInfo['openid'] . ' fail when autologin');
                return ;
            }
        }
        $ret = WxUserModel::findUserByOpenId($openInfo['openid']);
        if (!empty($ret)) {
            Session::setUserSession($ret['user_id'], $openInfo['openid']);
            $this->wxUserInfo = $ret;
            return ;
        } else { // create one
            $from = WxUserModel::SUBSCRIBE_FROM_ALREADY;
            if ((int)$wxUserInfo['subscribe'] == 0) {
                $from = WxUserModel::SUBSCRIBE_FROM_UNSUBSCRIBE;
            }
            $ret = WxUserModel::newWxUser($wxUserInfo, $from);
            if ($ret === false) {
                return ;
            }

            $ret = WxUserModel::findUserByOpenId($openInfo['openid']);
            if (!empty($ret)) {
                Session::setUserSession($ret['user_id'], $openInfo['openid']);
                $this->wxUserInfo = $ret;
                return ;
            } else {
                Log::error("create wx user fail! " . json_encode($wxUserInfo, JSON_UNESCAPED_UNICODE));
            }
        }
    }

    public function toDefaultLogin()
    {
        header('Location: /user/Login');
    }
}

