<?php
/**
 * @Author shaowei
 * @Date   2015-12-27
 */

namespace src\mall\model;

use \src\common\Cache;
use \src\common\Util;
use \src\common\Log;

class GoodsModuleModel
{
    public static function newOne(
        $title,
        $beginTime,
        $endTime,
        $sort
    ) {
        $data = array(
            'title' => $title,
            'begin_time' => $beginTime,
            'end_time' => $endTime,
            'sort' => $sort,
            'ctime' => CURRENT_TIME,
        );
        $ret = DB::getDB('w')->insertOne('m_goods_module', $data);
        if ($ret === false || (int)$ret <= 0) {
            return false;
        }
        return true;
    }

    public static function findAllValidModule($beginTime, $endTime)
    {
        $ret = DB::getDB()->fetchAll(
            'm_goods_module',
            '*',
            array('begin_time >=', 'end_time <'), array($beginTime, $endTime),
            array('and'),
            array('sort'), array('desc')
        );
        return $ret === false ? array() : $ret;
    }
}
