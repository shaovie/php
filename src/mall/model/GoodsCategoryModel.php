<?php
/**
 * @Author shaowei
 * @Date   2015-12-24
 */

namespace src\mall\model;

use \src\common\Cache;
use \src\common\Util;
use \src\common\Log;
use \src\common\DB;

class GoodsCategoryModel
{
    public static function newOne($categoryId, $name, $imageUrl)
    {
        $categoryId = self::genCategoryId($categoryId);
        if ($categoryId == false) {
            return false;
        }
        $data = array(
            'category_id' => $categoryId,
            'name' => $name,
            'image_url' => $imageUrl,
            'ctime' => CURRENT_TIME,
            'mtime' => CURRENT_TIME,
        );
        $ret = DB::getDB('w')->insertOne('g_category', $data);
        if ($ret === false || (int)$ret <= 0) {
            return false;
        }
        return true;
    }

    public static function checkBelongCategoryOrNot($masterId, $categoryId)
    {
        if ($masterId == $categoryId) {
            return true;
        }
        $level1 = (int)($masterId / 1000000);
        $level2 = (int)((int)($masterId / 1000) % 1000);
        $level3 = (int)($masterId % 1000);
        if ($level1 != 0 && $level2 == 0 && $level3 == 0) { // 一级分类
            if ((int)($categoryId / 1000000) == $level1) {
                return true;
            }
        } elseif ($level1 != 0 && $level2 != 0 && $level3 == 0) { // 二级分类
            if ((int)($categoryId / 1000) == $level1 * 1000 + $level2) {
                return true;
            }
        } else { // 三级
            return $masterId == $categoryId;
        }
        return false;
    }

    //= private methods
    private static function genCategoryId($categoryId)
    {
        $level1 = (int)($categoryId / 1000000);
        $level2 = (int)((int)($categoryId / 1000) % 1000);
        $level3 = (int)($categoryId % 1000);

        if ($level1 == 0) { // 增加一级分类
            $sql = 'select max(category_id) as m from g_category';
            $ret = DB::getDB('w')->rawQuery($sql);
            if ($ret === false) {
                return false;
            }
            if (empty($ret) || empty($ret[0]['m'])) {
                return 100000000; // 初始以100000000开始，虽然会少用9个，但会整齐好看一些
            }
            $max = (int)$ret[0]['m'];
            if ((int)($max / 1000000) == 999) {
                Log::fatal('category_id ' . $categoryId . ' level1 max = 999, out of limit!');
                return false;
            }
            return ((int)($max / 1000000) + 1) * 1000000;
        } else if ($level2 == 0 && $level3 == 0) { // 增加二级分类
            $sql = 'select max(category_id) as m from g_category where'
            . ' category_id >= ' . ($level1 * 1000000)
            . ' and category_id < ' . (($level1 + 1) * 1000000);
            $ret = DB::getDB('w')->rawQuery($sql);
            if ($ret === false) {
                return false;
            }
            if (empty($ret) || empty($ret[0]['m'])) {
                return $level1 * 1000000 + 1000;
            }
            $maxLevel2 = ((int)(((int)$ret[0]['m']) / 1000) % 1000);
            if ($maxLevel2 == 999) {
                Log::fatal('category_id ' . $categoryId . ' level2 max = 999, out of limit!');
                return false;
            }
            return $level1 * 1000000 + ($maxLevel2 + 1) * 1000;
        } else if ($level3 == 0) { // 增加三级分类
            $sql = 'select max(category_id) as m from g_category where'
            . ' category_id >= ' . ($level1 * 1000000 + $level2 * 1000)
            . ' and category_id < ' . ($level1 * 1000000 + ($level2 + 1) * 1000);
            $ret = DB::getDB('w')->rawQuery($sql);
            if ($ret === false) {
                return false;
            }
            if (empty($ret) || empty($ret[0]['m'])) {
                return $level1 * 1000000 + $level2 * 1000 + 1;
            }
            $maxLevel3 = ((int)$ret[0]['m']) % 1000;
            if ($maxLevel3 == 999) {
                Log::fatal('category_id ' . $categoryId . ' level3 max = 999, out of limit!');
                return false;
            }
            return $level1 * 1000000 + $level2 * 1000 + $maxLevel3 + 1;
        }
        Log::error('error category_id(' . $categoryId . ') when generate category id');
        return false;
    }
}

