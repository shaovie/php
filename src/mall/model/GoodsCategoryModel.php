<?php
/**
 * @Author shaowei
 * @Date   2015-12-24
 */

namespace src\mall\model;

use \src\common\Cache;
use \src\common\Util;
use \src\common\Log;

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
        $ret = Db::getDB('w')->insertOne('g_category', $data);
        if ($ret === false) {
            return false;
        }
        return true;
    }

    //= private methods
    private static function genCategoryId($categoryId)
    {
        $level1 = (int)($categoryId / 10000);
        $level2 = ((int)($categoryId / 100) % 100);
        $level3 = $categoryId % 100;

        if ($level1 == 0) { // 增加一级分类
            $sql = 'select max(category_id) as m from g_category';
            $ret = DB::getDB('w')->rawQuery($sql);
            if ($ret === false) {
                return false;
            }
            if (empty($ret) || empty($ret[0]['m'])) {
                return 100000; // 初始以100000开始，虽然会少用9个，但会整齐好看一些
            }
            $max = (int)$ret[0]['m'];
            if ((int)($max / 10000) == 99) {
                Log::fatal('category_id ' . $categoryId . ' level1 max = 99, out of limit!');
                return false;
            }
            return ((int)($max / 10000) + 1) * 10000;
        } else if ($level2 == 0 && $level3 == 0) { // 增加二级分类
            $sql = 'select max(category_id) as m from g_category where'
            . ' category_id >= ' . ($level1 * 10000)
            . ' and category_id < ' . (($level1 + 1) * 10000);
            $ret = DB::getDB('w')->rawQuery($sql);
            if ($ret === false) {
                return false;
            }
            if (empty($ret) || empty($ret[0]['m'])) {
                return $level1 * 10000 + 100;
            }
            $maxLevel2 = ((int)(((int)$ret[0]['m']) / 100) % 100);
            if ($maxLevel2 == 99) {
                Log::fatal('category_id ' . $categoryId . ' level2 max = 99, out of limit!');
                return false;
            }
            return $level1 * 10000 + ($maxLevel2 + 1) * 100;
        } else if ($level3 == 0) { // 增加三级分类
            $sql = 'select max(category_id) as m from g_category where'
            . ' category_id >= ' . ($level1 * 10000 + $level2 * 100)
            . ' and category_id < ' . ($level1 * 10000 + ($level2 + 1) * 100);
            $ret = DB::getDB('w')->rawQuery($sql);
            if ($ret === false) {
                return false;
            }
            if (empty($ret) || empty($ret[0]['m'])) {
                return $level1 * 10000 + $level2 * 100 + 1;
            }
            $maxLevel3 = ((int)$ret[0]['m']) % 100;
            if ($maxLevel3 == 99) {
                Log::fatal('category_id ' . $categoryId . ' level3 max = 99, out of limit!');
                return false;
            }
            return $level1 * 10000 + $level2 * 100 + $maxLevel3 + 1;
        }
        Log::error('error category_id(' . $categoryId . ') when generate category id');
        return false;
    }
}

