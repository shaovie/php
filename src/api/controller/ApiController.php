<?php
/**
 * @Author shaowei
 * @Date   2015-12-23
 */

namespace src\api\controller;

use \src\common\UserBaseController;

class ApiController extends UserBaseController
{
    public function __construct()
    {
        $this->doLogin();

        $this->checkLoginAndNotice();
    }
}
