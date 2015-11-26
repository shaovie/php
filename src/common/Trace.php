<?php
/**
 * @Author shaowei
 * @Date   2015-07-20
 */

namespace src\common;

class Trace
{
    private static $startTime;
    private static $mem;

    public static function start()
    {
        self::$startTime = microtime(true);
        self::$mem = memory_get_usage();
    }
    public static function end($func)
    {
        $diff = round(microtime(true) - self::$startTime) + 0.001;
        $memUsed = memory_get_usage()  - self::$mem;
        Log::debug($func . " cost " . $diff . " msec, mem use " . $memUsed);
    }
}

