<?php
/**
 * @Author shaowei
 * @Date   2015-07-19
 */

namespace src\common;

class HttpUtil
{
    public static function request($url, $data = false, $header = false, $timeout = 3)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);

        if (stripos($url, 'https:') !== false) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        if (!empty($header)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $ret = curl_exec($curl);
        curl_close($curl);
        return $ret;
    }

    public static function postSSL(
        $url,
        $data,
        $apiClientCertPem,
        $apiClientKeyPem,
        $rootcaPem,
        $timeout = 10)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout); //超时时间
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  

        curl_setopt($curl, CURLOPT_SSLCERT, $apiClientCertPem);
        curl_setopt($curl, CURLOPT_SSLKEY, $apiClientKeyPem);
        curl_setopt($curl, CURLOPT_CAINFO, $rootcaPem);

        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        $ret = curl_exec($curl);
        curl_close($curl);
        return $ret;
    }

    // 使用304优化首页
    public static function pageTo304(
        $expire,     // 用于多久检查一次页面是否有更新
        $uri,        // 用于索引这个缓存页
        $obj,        // 控制器对象
        $func,       // 控制器方法
        $params,     // 控制器方法所需参数
        $cacheControl = 1
    ) {
        if (PHP_OS != 'Linux') {
            return false;
        }
        $fileName = trim(trim($uri), '/');
        $fileName = str_replace('/', '_', $fileName);
        if (!empty($params)) {
            $fileName .= '?' . http_build_query($params);
        }

        $filePath = '/dev/shm/' . $fileName . '.html';
        $timeFilePath = $filePath . '.time';
        if (!is_file($filePath)) {
            touch($timeFilePath);
            if (self::makePageHtml($filePath, false, $obj, $func, $params) === false) {
                return false;
            }
            return self::echoNewPage($filePath, $cacheControl);
        }
        $lastCheckTime = filemtime($timeFilePath);
        $now = (int)CURRENT_TIME;
        if ($now - $lastCheckTime > $expire) {
            touch($timeFilePath, $now, $now);
            $ret = self::makePageHtml($filePath, true, $obj, $func, $params);
            if ($ret === false) {
                return false;
            } elseif ($ret === 1) {
                return self::echoNewPage($filePath, $cacheControl);
            }
        }

        $ftime = filemtime($filePath);
        $time  = gmdate('D, d M Y H:i:s', $ftime) . ' GMT';
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
            && strncmp($_SERVER['HTTP_IF_MODIFIED_SINCE'], $time, 29) == 0) {
            header('HTTP/1.x 304 Not Modified');
            header('Cache-Control: max-age=' . $cacheControl);
            return true;
        }
        return self::echoNewPage($filePath, $cacheControl);
    }

    //= private methods
    //
    private static function echoNewPage($filePath, $cacheControl)
    {
        $ftime = filemtime($filePath);
        $time  = gmdate('D, d M Y H:i:s', $ftime) . ' GMT';
        header('Cache-Control: max-age=' . $cacheControl);
        header('Last-Modified: ' . $time);
        echo file_get_contents($filePath);
        return true;
    }

    // 生成静态页面 生成新页面:1 页面无需没有变化:0 生成新页面失败:false
    private static function makePageHtml($filePath, $fileExist, $obj, $func, $params)
    {
        ob_start();
        call_user_func_array(array($obj, $func), $params);
        $html = ob_get_contents();
        ob_end_clean();

        if ($fileExist) {
            $newMd5 = md5($html);
            $oldMd5 = md5_file($filePath);
            if ($newMd5 != $oldMd5) {
                $tmpFileName = $filePath . '.' . getmypid();
                file_put_contents($tmpFileName , $html);
                return rename($tmpFileName, $filePath) ? 1 : false;
            }
            return 0;
        }

        return file_put_contents($filePath, $html) === false ? false : 1;
    }
}

