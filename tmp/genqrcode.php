<?php

include 'libs/phpqrcode/phpqrcode.php';

if (empty($_GET['data'])) {
    response(1, '请求参数为空', array());
}
$qrcodeData = $_GET['data'];
$qrcodeData = urldecode($qrcodeData);

$size = isset($_GET['size']) ? (int)$_GET['size'] : 8;
if ($size < 1 || $size > 20) {
    $size = 8;
}

$logo = '';
if (isset($_GET['logo'])) {
    if ($_GET['logo'] == 1) {
        $logo = 'qrcode/logo-margin.png';
    } else {
        $_GET['logo'] = trim(urldecode($_GET['logo']));
        if (strncmp($_GET['logo'], 'http', 4) == 0) {
            $logo = $_GET['logo'];
        }
    }
}

$fileName = md5($qrcodeData . $size . $logo);
$saveDir = 'qrcode/' . substr($fileName, 0, 2);
if (!file_exists($saveDir)) {
    mkdir($saveDir, 0777, true);
}

$path = $saveDir . '/' . $fileName . '.png';
$cdnNum = ord($fileName) % 4;
$imgUrl = 'http://cdn' . $cdnNum . '.taojinzi.com/' . $path;
if (file_exists($path)) {
    response(0, '', array('url' => $imgUrl));
}

QRcode::png($qrcodeData, $path, 'L', $size, 2);

if (!file_exists($path)) {
    response(1, '二维码生成失败', array());
}

if (!empty($logo)) {
    $qr = imagecreatefromstring(file_get_contents($path));
    $logo = imagecreatefromstring(file_get_contents($logo));
    $qrWidth = imagesx($qr); //二维码图片宽度
    $qrHeight = imagesy($qr); //二维码图片高度
    $logoWidth = imagesx($logo); //logo图片宽度
    $logoHeight = imagesy($logo); //logo图片高度
    $logoQrWidth = $qrWidth / 5;
    $scale = $logoWidth / $logoQrWidth;
    $logoQrHeight = $logoHeight / $scale;
    $fromWidth = ($qrWidth - $logoQrWidth) / 2;

    //重新组合图片并调整大小
    imagecopyresampled($qr, $logo, $fromWidth, $fromWidth, 0, 0, $logoQrWidth,
        $logoQrHeight, $logoWidth, $logoHeight);
    imagepng($qr, $path);
    if (!file_exists($path)) {
        response(1, '二维码生成失败', array());
    }
}

response(0, '', array('url' => $imgUrl));

function response($code, $msg, $result)
{
    $data['code'] = $code;
    $data['msg']  = $msg;
    $data['url']  = '';
    $data['result'] = $result;
    $data = json_encode($data);

    $callback = empty($_GET['callback']) ? false : $_GET['callback'];
    if (!empty($callback)) {
        echo $callback . '(' . $data . ')';
    } else {
        echo $data;
    }
    exit();
}
