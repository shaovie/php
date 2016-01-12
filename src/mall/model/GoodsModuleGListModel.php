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

class GoodsModuleGListModel
{
    public static function newOne(
        $moduleId,
        $goodsId,
        $sort
    ) {
        $data = array(
            'module_id' => $moduleId,
            'goods_id' => $goodsId,
            'sort' => $sort,
            'ctime' => CURRENT_TIME,
        );
        $ret = DB::getDB('w')->insertOne('m_goods_module_glist', $data);
        if ($ret === false || (int)$ret <= 0) {
            return false;
        }
        return true;
    }

    public static function getAllGoods($moduleId)
    {
        if (empty($moduleId)) {
            return array();
        }
        $ret = DB::getDB()->fetchAll(
            'm_goods_module_glist',
            '*',
            array('module_id'), array($moduleId),
            false,
            array('sort'), array('desc')
        );
        return $ret === false ? array() : $ret;
    }
}
