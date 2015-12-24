<?php
/**
 * @Author shaowei
 * @Date   2015-12-24
 */

namespace src\mall\model;

use \src\common\Cache;
use \src\common\Log;

class GoodsCommentLikeModel
{
    public static function newOne($userId, $commentId)
    {
        if (empty($userId)
            || empty($commentId)) {
            return false;
        }

        $data = array(
            'comment_id'=> $commentId,
            'user_id'   => $userId,
            'ctime'     => CURRENT_TIME,
        );
        $ret = Db::getDB('w')->insertOne('g_goods_comment_like', $data);
        if ($ret === false) {
            return false;
        }
        $ck = Cache::CK_GOODS_COMMENT_HAD_LIKE . $commentId . ':' . $userId;
        Cache::setex($ck, Cache::CK_GOODS_COMMENT_HAD_LIKE_EXPIRE, '1');
        return true;
    }

    public static function hadLiked($userId, $commentId)
    {
        $ck = Cache::CK_GOODS_COMMENT_HAD_LIKE . $commentId . ':' . $userId;
        $ret = Cache::get($ck);
        if ($ret === false) {
            $ret = DB::getDB()->fetchCount(
                'g_goods_comment_like',
                array('comment_id', 'user_id'), array($commentId, $userId),
                array('and')
            );
            if ($ret !== false) {
                Cache::setex($ck, Cache::CK_GOODS_COMMENT_HAD_LIKE_EXPIRE, (string)$ret);
            }
        }
        return (int)$ret > 0;
    }
}

