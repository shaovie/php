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
                    return $this->loginInWx($userInfo['openid']);
                } else {
                    return $this->loginDefault($userInfo['userId']);
                }
            }
        }
        return false;
    }

    public function hadLogin()
    {
        return empty($this->userInfo['id']);
    }

    public function loginInWx($openid)
    {
        $this->wxUserInfo = WxUserModel::findUserByOpenId($openid);
        if (!empty($this->wxUserInfo)) {
            $this->userInfo = UserModel::findUserById($this->wxUserInfo['user_id']);
        }
        return empty($this->wxUserInfo);
    }

    public function loginDefault($userId)
    {
        $this->userInfo = UserModel::findUserById($userId);
        return empty($this->userInfo);
    }

    public function toLogin()
    {
        if (Util::inWx()) {
            $this->wxLogin();
        }
    }

    public function wxLogin()
    {
        $openInfo = WxSDK::getOpenInfo('snsapi_base', WX_APP_ID, WX_APP_SECRET);
        if (empty($openInfo['openid'])) {
            return ;
        }
        $wxUserInfo = WxSDK::getUserInfo($openInfo['openid'], 'snsapi_base');
        if (empty($wxUserInfo)) { //
            $wxUserInfo = WxSDK::getUserInfo($openInfo['openid'], 'snsapi_base');
            if (empty($wxUserInfo)) { //
                return ;
            }
        }
        $ret = WxUserModel::findUserByOpenId($openInfo['openid']);
        if (!empty($ret)) {
            Session::setUserSession($ret['user_id'], $openInfo['openid']);
            $this->wxUserInfo = $ret;
            return ;
        } else { // create one
            $ret = WxUserModel::newWxUser($wxUserInfo);
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
}

