<?php
/**
 * @Author shaowei
 * @Date   2015-11-30
 */

namespace src\user\model;

use \src\common\DB;
use \src\common\Util;

class UserDetailModel
{
    public static function newOne($userId)
    {
        $data = array(
            'user_id' => $userId,
            'score' => 0,
            'ctime' => CURRENT_TIME,
            'mtime' => CURRENT_TIME
        );
        $ret = DB::getDB('w')->insertOne('u_user_detail', $data);
        if ($ret === false || (int)$ret <= 0) {
            return false;
        }
        self::findUserDetailById($userId, 'w');
        return true;
    }

    public static function findUserDetailById($userId, $fromDb = 'w')
    {
        if (empty($userId)) {
            return array();
        }
        $ck = Cache::CK_USER_DETAIL_INFO . $userId;
        $ret = Cache::get($ck);
        if ($ret !== false) {
            $ret = json_decode($ret, true);
        } else {
            $ret = DB::getDB($fromDb)->fetchOne(
                'u_user_detail',
                '*',
                array('user_id'), array($userId)
            );
            if ($ret !== false) {
                Cache::set($ck, json_encode($ret));
            }
        }
        if (empty($ret)) {
            return array();
        }
        return $ret;
    }

    private static function onUpdateData($userId)
    {
        Cache::del(Cache::CK_USER_DETAIL_INFO . $userId);
        self::findUserDetailById($userId, 'w');
    }
}

