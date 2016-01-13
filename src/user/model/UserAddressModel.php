<?php
/**
 * @Author shaowei
 * @Date   2015-11-30
 */

namespace src\user\model;

use \src\common\DB;
use \src\common\Util;
use \src\common\Cache;

class UserAddressModel
{
    const ADDR_TYPE_UNKNOW  = 0;
    const ADDR_TYPE_COMPANY = 1;
    const ADDR_TYPE_FAMILY  = 2;

    public static function newOne(
        $userId,
        $reName,
        $rePhone,
        $addrType,
        $provinceId,
        $cityId,
        $districtId,
        $detailAddr,
        $reIdCard,   // 收货人身份证
        $isDefault
    ) {
        if (empty($userId)) {
            return false;
        }

        $data = array(
            'user_id' => $userId,
            're_name' => $reName,
            're_phone' => $rePhone,
            'addr_type' => $addrType,
            'province_id' => $provinceId,
            'city_id' => $cityId,
            'district_id' => $districtId,
            'detail' => $detailAddr,
            're_id_card' => $reIdCard,
            'is_default' => $isDefault,
            'ctime' => CURRENT_TIME,
            'mtime' => CURRENT_TIME
        );
        $ret = DB::getDB('w')->insertOne('u_address', $data);
        if ($ret === false || (int)$ret <= 0) {
            return false;
        }
        self::onUpdateData($userId);
        return true;
    }

    public static function update($userId, $addrId, $data)
    {
        if (empty($userId) || empty($addrId)) {
            return false;
        }
        $ret = DB::getDB('w')->update(
            'u_address',
            $data,
            array('id', 'user_id'), array($addrId, $userId),
            array('and'),
            1
        );
        if ($ret === false) || (int)$ret <= 0) {
            return false;
        }
        self::onUpdateData($userId);
        return true;
    }

    public static function getAddrList($userId, $fromDb = 'w')
    {
        if (empty($userId)) {
            return array();
        }

        $ck = Cache::CK_USER_ADDR_LIST . $userId;
        $ret = Cache::get($ck);
        if ($ret !== false) {
            return json_decode($ret, true);
        }

        $ret = DB::getDB($fromDb)->fetchAll(
            'u_address',
            '*',
            array('user_id'), array($userId)
        );
        if ($ret !== false) {
            Cache::set($ck, json_encode($ret));
        } else {
            return array();
        }
        return $ret;
    }

    public static function getAddr($userId, $addrId)
    {
        $addrList = self::getAddrList($userId);
        if ($addrList !== false) {
            foreach ($addrList as $addr) {
                if ($addr['id'] == $addrId) {
                    return $addr;
                }
            }
        }
        return array();
    }

    public static function findDefaultAddr($userId, $fromDb = 'w')
    {
        if (empty($userId)) {
            return array();
        }
        $addrList = self::getAddrList($userId, $fromDb);
        if (empty($addrList)) {
            return array();
        }
        foreach ($addrList as $addr) {
            if ($addr['is_default'] == 1) {
                return $addr;
            }
        }
        return $addr[0];
    }

    public static function setDefaultAddr($userId, $addrId, $val = 1)
    {
        if (empty($userId) || empty($addrId)) {
            return false;
        }
        $ret = DB::getDB('w')->update(
            'u_address',
            array('is_default', $val),
            array('id', 'user_id'), array($addrId, $userId),
            array('and'),
            1
        );
        if ($ret === false) || (int)$ret <= 0) {
            return false;
        }
        self::onUpdateData($userId);
        return true;
    }

    public static function delOne($userId, $addrId)
    {
        if (empty($userId) || empty($addrId)) {
            return false;
        }
        $ret = DB::getDB('w')->delete(
            'u_address',
            array('id', 'user_id'), array($addrId, $userId),
            array('and'),
            1
        );
        if ($ret === false) || (int)$ret <= 0) {
            return false;
        }
        self::onUpdateData($userId);
        return true;
    }

    public static function clearDefaultAddr($userId)
    {
        $addr = self::findDefaultAddr($userId);
        if (empty($addr)) {
            return ;
        }
        self::setDefaultAddr($userId, $addr['id'], 0);
    }

    private static function onUpdateData($userId)
    {
        Cache::del(Cache::CK_USER_ADDR_LIST . $userId);
        self::getAddrList($userId, 'w');
    }
}

