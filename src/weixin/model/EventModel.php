<?php
/**
 * @Author shaowei
 * @Date   2015-09-17
 */

namespace src\weixin\model;

user \src\common\WxSDK;
user \src\user\model\WxUserModel;

class EventModel
{
    const MIN_SCENE_ID       = 500000000; // 最多5亿用户

    // 下面定义使用场景二维码相关的活动，5亿为一个区间
    const QRCODE_XXXXX       = 500000000; // XXXXX活动

    // 如果是业务相关的消息，返回true，否则返回false以中转到多客服
    public static function onText($openid, $content)
    {
        return false;
    }

    public static function onScan($openid, $sceneId)
    {
        self::onSceneQrCode($openid, $sceneId);
    }

    // 普通关注
    public static function onSubscribe($openid)
    {
        self::onUserSubscribe($openid, 'subscribe');
    }

    // 扫码关注
    public static function onScanSubscribe($openid, $sceneId)
    {
        self::onUserSubscribe($openid, 'scansubscribe');
        self::onSceneQrCode($openid, $sceneId);
    }

    // 相对于公众号活跃（针对48小时内的客服消息）
    public static function onActivateForGZH($openid)
    {
        WxUserModel::onActivateForGZH($openid);
    }

    //= private method
    //
    private static function onSubscribeUser($openid, $from)
    {
        $wxUserInfo = WxUserModel::findUserByOpenId($openid);
        if (empty($wxUserInfo)) {
            $wxUserInfo = WxSDK::getUserInfo($openid, 'snsapi_base');
            if (empty($wxUserInfo)) {
                Log::warng('get wx:' . $openid . ' userinfo fail where on ' . $from);
            } else {
                WxUserModel::newWxUser($wxUserInfo);
            }
        }
    }

    private static function onSceneQrCode($openid, $sceneId)
    {
        if ($sceneId >= self::QRCODE_XXXXX
            || $sceneId < (self::QRCODE_XXXXX + self::MIN_SCENE_ID)) {
            ;
        }
    }
}

