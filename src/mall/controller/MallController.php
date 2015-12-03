<?php
/**
 * @Author shaowei
 * @Date   2015-09-17
 */

namespace src\mall\controller;

use \src\common\UserBaseController;

class MallController extends UserBaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->module = 'mall';

        if (!$this->autoLogin()) {
            $this->toLogin();
        }
    }
}

