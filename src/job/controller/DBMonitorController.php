<?php
/**
 * @Author shaowei
 * @Date   2015-12-02
 */

namespace src\job\controller;

use \src\common\DB;
use \src\job\model\AsyncModel;

class DBMonitorController extends JobController
{
    public function monitor()
    {
        $beginTime = time();
        do {
            do {
                break;
            } while (true);

            if (time() - $beginTime > 30) { // 30秒脚本重新执行一次
                break;
            }
            usleep(200000);
        } while (true);
    }

    private function doOpt($data)
    {
    }

    //= protected methods
    protected function run($idx) { }
}

