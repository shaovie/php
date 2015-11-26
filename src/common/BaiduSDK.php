<?php
/**
 * @Author shaowei
 * @Date   2015-08-22
 */

namespace src\common;

class BaiduSDK
{
    const APPKEY = 'xx';
    const SECURITY_KEY = 'xx';
    const APIKEY = 'xx';

    public static function getLocationByIP($ip)
    {
        $ck = Cache::CK_BAIDU_IP_TO_LOCATION . $ip;
        $result = Cache::get($ck);
        if ($result !== false) {
            return json_decode($result, true);
        }

        $querystringArrays = array(
            'ak' => self::APPKEY,
            'ip' => $ip,
            'coor' => 'bd09ll',
        );
        $uri = '/location/ip';
        $sn = self::caculateAKSN(self::APPKEY, self::SECURITY_KEY, $uri, $querystringArrays);

        $url = "http://api.map.baidu.com/location/ip?ak=%s&ip=%s&sn=%s&coor=bd09ll";
        $url = sprintf($url, self::APPKEY, $ip, $sn); 
        $ret = HttpUtil::request($url, false, false, 2);
        if ($ret === false) {
            Log::warng('baidu ' . __METHOD__ . ' - request timeout or fail!');
            return false;
        }
        $ret = json_decode($ret, true);
        if ($ret['status'] != 0) {
            Log::warng('baidu ' . __METHOD__ . ' - request error status=' . $ret['status']);
            return false;
        }

        $ret = $ret['content']['address_detail'];
        if (!empty($ret)) {
            Cache::set($ck, $ret);
        }
        return $ret;
    }
    
    // location=39.983424,116.322987(纬度，经度)
    public static function getAddrByLocation($location)
    {
        if (empty($location) || strlen($location < 8)) {
            return false;
        }
        $ck = Cache::CK_BAIDU_LAT_LNG_TO_ADDR . $location;
        $result = Cache::get($ck);
        if ($result !== false) {
            return json_decode($result, true);
        }                
        $querystringArrays = array(
            'ak' => self::APPKEY,
            'location' => $location,
            'output' => 'json',
            'coordtype' => 'bd09ll'
        );
        $uri = '/geocoder/v2/';
        $sn = self::caculateAKSN(self::APPKEY, self::SECURITY_KEY, $uri, $querystringArrays);
        
        $url = 'http://api.map.baidu.com/geocoder/v2/?ak=%s&location=%s&sn=%s&output=%s&coordtype=%s';
        $url = sprintf($url, self::APPKEY, urlencode($location), $sn, 'json', 'bd09ll');
        $ret = HttpUtil::request($url, false, false, 2);
        if ($ret === false) {
            Log::warng('baidu ' . __METHOD__ . ' - request timeout or fail Location=' . $location);
            return false;
        }
        $retDecode = json_decode($ret, true);
        if ($retDecode['status'] != 0) {
            Log::warng('baidu ' . __METHOD__ . ' - error status=' . $retDecode['status'] . ' Location=' . $location);
            return false;
        }
        if (!empty($retDecode)) {
            Cache::set($ck, json_encode($retDecode['result']['addressComponent']));
        }
        return $retDecode['result']['addressComponent'];
    }

    public static function getCityCodeByName($cityName)
    {
        if (empty($cityName)) {
            return array();
        }
        $cityName = trim($cityName);
        $cityName = mb_ereg_replace('市*$', '', $cityName);

        $ck = Cache::CK_BAIDU_CITY_INFO . $cityName;
        $result = Cache::get($ck);
        if ($result !== false) {
            return json_decode($result, true);
        }                
        $url = 'http://apistore.baidu.com/microservice/cityinfo?cityname=' . urlencode($cityName);
        $ret = HttpUtil::request($url, false, false, 2);
        if ($ret === false) {
            Log::warng('baidu ' . __METHOD__ . ' - request timeout or fail city=' . $cityName);
            return false;
        }
        $ret = json_decode($ret, true);
        if (empty($ret['retData'])) {
            Log::warng('baidu ' . __METHOD__ . ' - fail city=' . $cityName . ' ' . json_encode($ret));
            return false;
        }
        return $ret['retData']['cityCode'];
    }

    // coords=39.983424,116.322987
    // return array('x' => '39.983424', 'y' => '39.983424')
    public static function wxGeoConvToBaidu($coords)
    {
        if (empty($coords) || strlen($coords) < 8) {
            return false;
        }
        $ck = Cache::CK_BAIDU_WX_GEOCONV . $coords;
        $result = Cache::get($ck);
        if ($result !== false) {
            return json_decode($result, true);
        }                
        $querystringArrays = array(
            'ak' => self::APPKEY,
            'coords' => $coords,
            'output' => 'json',
            'from' => '3',
            'to' => '5',
        );
        $uri = '/geoconv/v1/';
        $sn = self::caculateAKSN(self::APPKEY, self::SECURITY_KEY, $uri, $querystringArrays);
        
        $url = 'http://api.map.baidu.com/geoconv/v1/?ak=%s&coords=%s&output=%s&from=%s&to=%s&sn=%s';
        $url = sprintf($url, self::APPKEY, urlencode($coords), 'json', '3', '5', $sn);
        $ret = HttpUtil::request($url, false, false, 2);
        if ($ret === false) {
            Log::warng('baidu ' . __METHOD__ . ' - request timeout or fail coords=' . $coords);
            return false;
        }
        $retDecode = json_decode($ret, true);
        if ($retDecode['status'] != 0) {
            Log::warng('baidu ' . __METHOD__ . ' - error status=' . $retDecode['status'] . ' coords=' . $coords);
            return false;
        }
        if (!empty($retDecode)) {
            Cache::set($ck, json_encode($retDecode['result'][0]));
        }
        return $retDecode['result'][0];
    }

    //= private methods
    private static function caculateAKSN($ak, $sk, $uri, $querystringArrays, $method = 'GET')
    {  
        if ($method === 'POST') {
            ksort($querystringArrays);
        }
        $querystring = http_build_query($querystringArrays);
        return md5(urlencode($uri . '?' . $querystring . $sk));
    }

}

