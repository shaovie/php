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

class GoodsCommentModel
{
    const COMMENT_ST_INVALID = 0;  // 无效
    const COMMENT_ST_VALID   = 1;  // 无效

    public static function newOne(
        $userId,
        $goodsId,
        $orderId,
        $nickname,
        $score,
        $content,
        $imageUrls
    ) {
        if (empty($goodsId)
            || empty($userId)
            || empty($orderId)
            || empty($content)) {
            return false;
        }

        $data = array(
            'user_id'   => $userId,
            'goods_id'  => $goodsId,
            'order_id'  => $orderId,
            'nickname'  => Util::emojiEncode($nickname),
            'score'     => $score,
            'content'   => Util::emojiEncode($content),
            'image_urls'=> $imageUrls,
            'state'     => self::COMMENT_ST_VALID,
            'ctime'     => CURRENT_TIME,
        );
        $ret = DB::getDB('w')->insertOne('g_goods_comment', $data);
        if ($ret === false) {
            return false;
        }
        $ck = Cache::CK_GOODS_HAD_COMMENT . $userId . ':' . $orderId . ':' . $goodsId;
        Cache::setEx($ck, Cache::CK_GOODS_HAD_COMMENT_EXPIRE, '1');
        return true;
    }

    public static function getSomeComment($goodsId, $nextId, $size)
    {
        if (empty($goodsId) || $size <= 0) {
            return array();
        }
        $nextId = (int)$nextId;
        if ($nextId > 0) {
            $ret = DB::getDB()->fetchSome(
                'g_goods_comment',
                '*',
                array('goods_id', 'state', 'id<'), array($goodsId, self::COMMENT_ST_VALID, $nextId),
                array('and', 'and'),
                array('id'), array('desc'),
                array($size)
            );
        } else {
            $ret = DB::getDB()->fetchSome(
                'g_goods_comment',
                '*',
                array('goods_id', 'state'), array($goodsId, self::COMMENT_ST_VALID),
                array('and'),
                array('id'), array('desc'),
                array($size)
            );
        }
        if (empty($ret)) {
            return array();
        }
        foreach ($ret as &$item) {
            $item['nickname'] = Util::emojiDecode($item['nickname']);
            $item['content'] = Util::emojiDecode($item['content']);
            $item['kf_reply'] = Util::emojiDecode($item['kf_reply']);
        }
        return empty($ret) ? array() : $ret;
    }

    // return true or false
    public static function hadCommented($userId, $orderId, $goodsId)
    {
        $ck = Cache::CK_GOODS_HAD_COMMENT . $userId . ':' . $orderId . ':' . $goodsId;
        $ret = Cache::get($ck);
        if ($ret === false) {
            $ret = DB::getDB()->fetchCount(
                'g_goods_comment',
                array('user_id', 'goods_id', 'order_id'), array($userId, $goodsId, $orderId),
                array('and', 'and')
            );
            if ($ret !== false) {
                Cache::setex($ck, Cache::CK_GOODS_HAD_COMMENT_EXPIRE, (string)$ret);
            }
        }
        return (int)$ret > 0;
    }
}

