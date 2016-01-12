<?php
/**
 * @Author shaowei
 * @Date   2015-12-27
 */

namespace src\mall\model;

use \src\common\Cache;
use \src\common\Util;
use \src\common\Log;
use \src\common\DB;

class ActivityModel
{
    public static function newOne(
        $actType,
        $title,
        $description,
        $imageUrl,
        $imageUrls,
        $beginTime,
        $endTime,
        $sort
    ) {
        if (empty($actType)) {
            return array();
        }
        $data = array(
            'act_type' => $actType,
            'title' => $title,
            'description' => $description,
            'image_url' => $imageUrl,
            'image_urls' => $imageUrls,
            'begin_time' => $beginTime,
            'end_time' => $endTime,
            'ctime' => CURRENT_TIME,
        );
        $ret = DB::getDB('w')->insertOne('m_activity', $data);
        if ($ret === false || (int)$ret <= 0) {
            return false;
        }
        return true;
    }

    public static function findAllValidActivity($beginTime, $endTime)
    {
        $ret = DB::getDB()->fetchAll(
            'm_activity',
            '*',
            array('begin_time >=', 'end_time <'), array($beginTime, $endTime),
            array('and')
        );
        return $ret === false ? array() : $ret;
    }
}
