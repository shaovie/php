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

class BannerModel
{
    const SHOW_AREA_HOME_TOP = 1; // 首页顶部

    public static function newOne(
        $showArea,
        $beginTime,
        $endTime,
        $imageUrl,
        $linkUrl,
        $remark,
        $sort
    ) {
        $data = array(
            'show_area' => $showArea,
            'begin_time' => $beginTime,
            'end_time' => $endTime,
            'image_url' => $imageUrl,
            'link_url' => $linkUrl,
            'remark' => $remark,
            'sort' => $sort,
            'ctime' => CURRENT_TIME,
        );
        $ret = DB::getDB('w')->insertOne('m_banner', $data);
        if ($ret === false || (int)$ret <= 0) {
            return false;
        }
        return true;
    }

    public static function findAllValidBanner($beginTime, $endTime, $showArea)
    {
        $ret = DB::getDB()->fetchAll(
            'm_banner',
            '*',
            array('begin_time >=', 'end_time <', 'show_area'), array($beginTime, $endTime, $showArea),
            array('and', 'and'),
            array('sort'), array('desc')
        );
        return $ret === false ? array() : $ret;
    }
}
