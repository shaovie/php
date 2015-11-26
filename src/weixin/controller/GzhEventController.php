<?php
/**
 * @Author shaowei
 * @Date   2015-08-18
 */

namespace src\weixin\controller;

use \src\common\Cache;
use \src\common\Log;
use \src\common\WxSDK;
use \src\common\Util;
use \src\weixin\model\EventModel;

class GzhEventController extends WeiXinController
{
    public function test()
    {
        $openInfo = WxSDK::getOpenInfo('snsapi_base', WX_APP_ID, WX_APP_SECRET);
        var_dump($openInfo);
        header('Location: /weixin/GzhEvent/test2');
    }
    public function test2()
    {
        $openInfo = WxSDK::getOpenInfo('snsapi_userinfo', WX_APP_ID, WX_APP_SECRET);
        var_dump($openInfo);
        echo '<br/>';
        echo '<br/>';
        var_dump(WxSDK::getUserInfo($openInfo['openid'], 'snsapi_userinfo', $openInfo['access_token']));
    }

    public function callback()
    {
        if (!$this->checkSignature()) {
            exit();
        }
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            echo $_GET['echostr'];
            exit();
        }

        $data = file_get_contents("php://input");
        $postObj = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
        Log::rinfo(json_encode($postObj));
        $postData = json_decode(json_encode($postObj), true);

        switch ($postData['MsgType']) {
        case 'event':
            $this->handleEvent($postData);
            break;
        case 'text':
            $this->handleText($postData);
            break;
        case 'image':
            $this->handleImage($postData);
            break;
        case 'voice':
            $this->handleVoice($postData);
            break;
        }
        exit();
    }

    //= private methods
    private function handleEvent($postData)
    {
        if ($postData['Event'] == 'SCAN') { // 扫描二维码
          $this->onScan($postData);
        } elseif ($postData['Event'] == 'subscribe') { // 订阅
            $this->onSubscribe($postData);
        } elseif ($postData['Event'] == 'unsubscribe') { // 取消订阅
            $this->onUnsubscribe($postData);
        } elseif ($postData['Event'] == 'LOCATION') { // 上报地理位置
            $this->onLocation($postData);
        } elseif ($postData['Event'] == 'VIEW') { // 点击跳转菜单
            $this->onView($postData);
        } elseif ($postData['Event'] == 'CLICK') { // 点击菜单
            $this->onClick($postData);
        }
    }

    private function handleText($postData)
    {
        EventModel::onText($postData);
    }

    private function handleImage($postData)
    {
    }
    private function handleVoice($postData)
    {
    }

    private function onScan($postData)
    {
        $sceneId = $postData['EventKey'];
        $openid  = $postData['FromUserName'];

        EventModel::onScan($openid);
    }

    private function onSubscribe($postData)
    {
        $openid = $postData['FromUserName'];

        if (strncmp($postData['EventKey'], 'qrscene_', 8) == 0) { // 扫描场景二维码的关注
            $sceneId = (int)substr($postData['EventKey'], 8); 
        } else { // 普通用户关注
        }
    }

    private function onUnsubscribe($postData)
    {
        $openid = $postData['FromUserName'];
    }

    private function onLocation($postData)
    {
        $openid = $postData['FromUserName'];
        $lat = $postData['Latitude'];
        $lng = $postData['Longitude'];
    }

    private function onView($postData)
    {
        $openid = $postData['FromUserName'];
    }

    private function onClick($postData)
    {
    }

    private function checkSignature()
    {
        $arr = array('zzczzc', $_GET['timestamp'], $_GET['nonce']);
        sort($arr, SORT_STRING);
        $sigStr = implode($arr);

        return sha1($sigStr) == $_GET['signature'];
    }
}

