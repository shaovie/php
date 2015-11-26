<?php
/**
 * @author cuishaowei
 * @date   2015-06-23
 */

namespace src\common;

class Log
{
    private static $debugfd = false;
    private static $rinfofd = false;
    private static $warngfd = false;
    private static $errorfd = false;
    private static $fatalfd = false;
    private static $payfd   = false;

    const DEBUG = 1;
    const RINFO = 2;
    const WARNG = 3;
    const ERROR = 4;
    const FATAL = 5;
    const PAY   = 6;

    private static function log($type, $str)
    {
        $fd = false;
        if ($type === self::DEBUG) {
            if (self::$debugfd === false) {
                self::$debugfd = @fopen(LOG_DIR . '/debug-' . date('Y-m-d') . '.log', 'a+');
            }
            $fd = self::$debugfd;
        } elseif ($type === self::RINFO) {
            if (self::$rinfofd === false) {
                self::$rinfofd = @fopen(LOG_DIR . '/rinfo-' . date('Y-m-d') . '.log', 'a+');
            }
            $fd = self::$rinfofd;
        } elseif ($type === self::WARNG) {
            if (self::$warngfd === false) {
                self::$warngfd = @fopen(LOG_DIR . '/warng-' . date('Y-m-d') . '.log', 'a+');
            }
            $fd = self::$warngfd;
        } elseif ($type === self::ERROR) {
            if (self::$errorfd === false) {
                self::$errorfd = @fopen(LOG_DIR . '/error-' . date('Y-m-d') . '.log', 'a+');
            }
            $fd = self::$errorfd;
        } elseif ($type === self::FATAL) {
            if (self::$fatalfd === false) {
                self::$fatalfd = @fopen(LOG_DIR . '/fatal-' . date('Y-m-d') . '.log', 'a+');
            }
            $fd = self::$fatalfd;
        } elseif ($type === self::PAY) {
            if (self::$payfd === false) {
                self::$payfd = @fopen(LOG_DIR . '/pay-' . date('Y-m-d') . '.log', 'a+');
            }
            $fd = self::$payfd;
        }

        if ($fd !== false) {
            $logStr = date('Y-m-d H:i:s') . ' > ' . $str . PHP_EOL;
            fwrite($fd, $logStr, strlen($logStr));
        }
    }

    public static function debug($str) { self::log(self::DEBUG, $str); }
    public static function rinfo($str) { self::log(self::RINFO, $str); }
    public static function warng($str) { self::log(self::WARNG, $str); }
    public static function error($str) { self::log(self::ERROR, $str); }
    public static function fatal($str) { self::log(self::FATAL, $str); }
    public static function pay($str)   { self::log(self::PAY,   $str); }
}
