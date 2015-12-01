<?php
/**
 * @Author shaowei
 * @Date   2015-11-30
 */

namespace src\user\model;

user \src\common\DB;
user \src\common\Util;

class UserModel
{
    public static function newUser(
        $phone,
        $nickname,
        $sex,
        $headimgurl,
        $state
    ) {
        if (DB::getDB('w')->beginTransaction() === false) {
            return false;
        }
        if (!empty($phone)) {
            $ret = self::findUserByPhone($phone);
            if (!empty($ret)) {
                DB::getDB('w')->rollBack();
                return false;
            }
        }
        $data = array(
            'phone' => $phone,
            'nickname' => Util::emojiEncode($nickname),
            'sex' => $sex,
            'headimgurl' => $headimgurl,
            'state' => $state,
            'ctime' => CURRENT_TIME
        );
        $ret = Db::getDB('w')->insertOne('u_user', $data);
        if ($ret === false) {
            DB::getDB('w')->rollBack();
            return false;
        }
        return DB::getDB('w')->commit();
    }

    public static function findUserById($userId)
    {
        if (empty($userId)) {
            return array();
        }
        $ret = DB::getDB()->fetchOne(
            'u_user',
            array('id'), array($userId),
        );
        if (empty($ret)) {
            return array();
        }
        $ret['nickname'] = Util::emojiDecode($ret['nickname']);
        return $ret;
    }

    public static function findUserByPhone($phone)
    {
        if (empty($phone)) {
            return array();
        }
        $ret = DB::getDB()->fetchOne(
            'u_user',
            array('phone'), array($phone),
        );
        if (empty($ret)) {
            return array();
        }
        $ret['nickname'] = Util::emojiDecode($ret['nickname']);
        return $ret;
    }
}

