<?php
/**
 * @Author shaowei
 * @Date   2015-11-30
 */

namespace src\user\model;

user \src\common\DB;
user \src\common\Util;

class WxUserModel
{
    const SUBSCRIBE_FROM_UNSUBSCRIBE = 0;
    const SUBSCRIBE_FROM_ALREADY = 1;
    const SUBSCRIBE_FROM_COMMON = 2;
    const SUBSCRIBE_FROM_SCAN_SCENE_CODE = 3;

    public static function newWxUser($wxUserInfo, $from)
    {
        if (empty($wxUserInfo)) {
            return false;
        }
        $data = array(
            'user_id' => 0,
            'openid' => $wxUserInfo['openid'],
            'nickname' => Util::emojiEncode((isset($wxUserInfo['nickname']) ? $wxUserInfo['nickname'] : '')),
            'sex' => isset($wxUserInfo['sex']) ? $wxUserInfo['sex'] : 0,
            'headimgurl' => isset($wxUserInfo['headimgurl']) ? $wxUserInfo['headimgurl'] : '',
            'province' => isset($wxUserInfo['province']) ? $wxUserInfo['province'] : '',
            'city' => isset($wxUserInfo['city']) ? $wxUserInfo['city'] : '',
            'subscribe' => isset($wxUserInfo['subscribe']) ? $wxUserInfo['subscribe'] : 0,
            'subscribe_time' => isset($wxUserInfo['subscribe_time']) ? $wxUserInfo['subscribe_time'] : 0,
            'subscribe_from' => intval($from),
            'unionid' => isset($wxUserInfo['unionid']) ? $wxUserInfo['unionid'] : '',
            'ctime' => CURRENT_TIME,
            'atime' => CURRENT_TIME,
        );
        return DB::getDB('w')->insertOne('u_wx_user', $data);
    }

    public static function updateWxUserInfo($userInfo, $wxUserInfo, $from)
    {
        $openid = $wxUserInfo['openid'];
        if (empty($openid) || empty($wxUserInfo) || empty($userInfo)) {
            return false;
        }

        $data = array();
        if (isset($wxUserInfo['nickname'])
            && $userInfo['nickname'] != $wxUserInfo['nickname']) {
            $data['nickname'] = Util::emojiEncode($wxUserInfo['nickname']);
        }
        if (isset($wxUserInfo['sex'])
            && $userInfo['sex'] != $wxUserInfo['sex']) {
            $data['sex'] = $wxUserInfo['sex'];
        }
        if (isset($wxUserInfo['headimgurl'])
            && $userInfo['headimgurl'] != $wxUserInfo['headimgurl']) {
            $data['headimgurl'] = $wxUserInfo['headimgurl'];
        }
        if (isset($wxUserInfo['province'])
            && $userInfo['province'] != $wxUserInfo['province']) {
            $data['province'] = $wxUserInfo['province'];
        }
        if (isset($wxUserInfo['city'])
            && $userInfo['city'] != $wxUserInfo['city']) {
            $data['city'] = $wxUserInfo['city'];
        }
        if (isset($wxUserInfo['subscribe'])
            && $userInfo['subscribe'] != $wxUserInfo['subscribe']) {
            $data['subscribe'] = $wxUserInfo['subscribe'];
        }
        if (isset($wxUserInfo['subscribe_time'])
            && $userInfo['subscribe_time'] != $wxUserInfo['subscribe_time']) {
            $data['subscribe_time'] = $wxUserInfo['subscribe_time'];
        }
        if ($userInfo['subscribe_from'] == 0) { // 仅记首次
            $data['subscribe_from'] = $from;
        }
        if (isset($wxUserInfo['unionid'])
            && $userInfo['unionid'] != $wxUserInfo['unionid']) {
            $data['unionid'] = $wxUserInfo['unionid'];
        }

        if (empty($data)) {
            return true;
        }

        $ret = DB::getDB('w')->update(
            'u_wx_user',
            $data,
            array('openid'), array($openid)
        );
        self::onUpdateData($openid);
        return $ret !== false;
    }

    public static function findUserByUserId($userId)
    {
        if (empty($userId)) {
            return array();
        }

        $ck = Cache::CK_WX_USER_INFO_FOR_UID . $userId;
        $ret = Cache::get($ck);
        if ($ret !== false) {
            $ret = json_decode($ret, true);
        } else {
            $ret = DB::getDB('w')->fetchOne(
                'u_wx_user',
                array('user_id'), array($userId),
            );
            if ($ret !== false) {
                Cache::set($ck, json_encode($ret));
            }
        }
        if (empty($ret)) {
            return array();
        }
        $ret['nickname'] = Util::emojiDecode($ret['nickname']);
        return $ret;
    }

    public static function findUserByOpenId($openid)
    {
        if (empty($openid)) {
            return array();
        }

        $ck = Cache::CK_WX_USER_INFO . $openid;
        $ret = Cache::get($ck);
        if ($ret !== false) {
            $ret = json_decode($ret, true);
        } else {
            $ret = DB::getDB('w')->fetchOne(
                'u_wx_user',
                array('openid'), array($openid),
            );
            if ($ret !== false) {
                Cache::set($ck, json_encode($ret));
            }
        }
        if (empty($ret)) {
            return array();
        }
        $ret['nickname'] = Util::emojiDecode($ret['nickname']);
        return $ret;
    }

    public static function onActivateForGZH($openid)
    {
        if (empty($openid)) {
            return ;
        }
        $ret = DB::getDB('w')->update(
            'u_wx_user',
            array('atime' => CURRENT_TIME),
            array('openid'), array($openid)
        );
        self::onUpdateData($openid);
    }

    private static function onUpdateData($openid)
    {
        $data = self::findUserByOpenId($openid);
        Cache::del(Cache::CK_WX_USER_INFO . $openid);
        if (!empty($data['user_id'])) {
            Cache::del(Cache::CK_WX_USER_INFO_FOR_UID . $data['user_id']);
        }
    }
}

