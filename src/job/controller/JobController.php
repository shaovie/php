<?php
/**
 * @Author shaowei
 * @Date   2015-11-30
 */

namespace src\job\controller;

use \src\common\Log;

abstract class JobController
{
    public function __construct()
    {
        if (php_sapi_name() != 'cli') {
            exit('error');
        }
        set_time_limit(0);
    }

    abstract protected function run($idx);

    final protected function spawnTask($concurrentNum)
    {
        $childPids = array();
        for ($i = 0; $i < $concurrentNum; $i++) {
            $childPid = pcntl_fork();
            if ($childPid == -1) {
                Log::fatal('cli - fork fail!');
                exit();
            } else if ($childPid > 0) { // parent
                $childPids[] = $childPid;
            } else { // child
                $this->run($i);
                exit();
            }
        }

        foreach ($childPids as $childPid) {
            $status = 0;
            pcntl_waitpid($childPid, $status);
        }
    }
}

