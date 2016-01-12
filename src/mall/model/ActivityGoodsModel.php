<?php
/**
 * @Author shaowei
 * @Date   2015-12-27
 */

namespace src\mall\model;

use \src\common\Cache;
use \src\common\Util;
use \src\common\Log;

class ActivityGoodsModel
{
    public static function findSomeValidActGoods($actId, $beginTime, $endTime, $nextId, $size)
    {
        if (empty($actId) || $size <= 0) {
            return array();
        }
        $ret = DB::getDB()->fetchSome(
            'm_activity_goods',
            '*',
            array('act_id', 'begin_time >=', 'end_time <', 'id>'), array($actId, $beginTime, $endTime, $nextId),
            array('and', 'and', 'and'),
            array('id'), array('asc'),
            array($size)
        );
        return $ret === false ? array() : $ret;
    }
}
