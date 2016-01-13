<?php
/**
 * @Author shaowei
 * @Date   2015-11-30
 */

namespace src\user\controller;

use \src\common\UserBaseController;

class UserController extends UserBaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->module = 'user';
    }
}

