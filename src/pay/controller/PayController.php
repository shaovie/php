<?php
/**
 * @Author shaowei
 * @Date   2015-09-17
 */

namespace src\pay\controller;

use \src\common\BaseController;

class PayController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->module = 'pay';
    }
}

