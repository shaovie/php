<?php
/**
 * @Author shaowei
 * @Date   2015-12-03
 */

namespace src\mall\controller;

use \src\common\BaseController;

class LogoutController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->module = 'user';
    }

    public function index()
    {
        $this->display('xx');
    }
}

