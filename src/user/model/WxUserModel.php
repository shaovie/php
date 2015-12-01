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
    public static function newWxUser($wxUserInfo)
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
            'unionid' => isset($wxUserInfo['unionid']) ? $wxUserInfo['unionid'] : '',
            'ctime' => CURRENT_TIME,
            'atime' => CURRENT_TIME,
        );
        return DB::getDB('w')->insertOne('u_wx_user', $data);
    }

    public static function findUserByOpenId($openid)
    {
        if (empty($openid)) {
            return array();
        }
        $ret = DB::getDB()->fetchOne(
            'u_wx_user',
            array('openid'), array($openid),
        );
        if (empty($ret)) {
            return array();
        }
        $ret['nickname'] = Util::emojiDecode($ret['nickname']);
        return $ret;
    }

    public static function onActivateForGZH($openid)
    {
        $ret = DB::getDB('w')->update(
            'u_wx_user',
            array('atime' => CURRENT_TIME),
            array('openid'), array($openid)
        );
    }
}

