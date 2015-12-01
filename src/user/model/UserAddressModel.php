<?php
/**
 * @Author shaowei
 * @Date   2015-11-30
 */

namespace src\user\model;

user \src\common\DB;
user \src\common\Util;

class UserAddressModel
{
    public static function newAddress(
        $userId,
        $reName,
        $rePhone,
        $addrType,
        $provinceId,
        $cityId,
        $districtId,
        $detailAddr,
        $reIdCard    // 身份证
        $isDefault,
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
            'ctime' => CURRENT_TIME
        );
        Cache::del(Cache::CK_USER_ADDR_LIST . $userId);
        return Db::getDB('w')->insertOne('u_user_address', $data);
    }

    public static function getAddrList($userId)
    {
        if (empty($userId)) {
            return array();
        }

        $ck = Cache::CK_USER_ADDR_LIST . $userId;
        $ret = Cache::get($ck);
        if ($ret !== false) {
            return json_decode($ret, true);
        }

        $ret = DB::getDB()->fetchAll(
            'u_user_address',
            '*',
            array('user_id'), array($userId),
        );
        if ($ret !== false) {
            Cache::set($ck, json_encode($ret));
        }
        return $ret;
    }

    public static function findDefaultAddr($userId)
    {
        if (empty($userId)) {
            return array();
        }
        $addrList = self::getAddrList($userId);
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

    public static function setDefaultAddr($userId, $addrId)
    {
        if (empty($userId) || empty($addrId)) {
            return false;
        }
        $ret = DB::getDB('w')->update(
            'u_user_address',
            array('is_default', 1),
            array('id', 'user_id'), array($addrId, $userId)
        );
        Cache::del(Cache::CK_USER_ADDR_LIST . $userId);
        return $ret !== false;
    }
}

