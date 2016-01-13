<?php
/**
 * @Author shaowei
 * @Date   2015-09-17
 */

namespace src\weixin\controller;

use \src\common\BaseController;

class WeiXinController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->module = 'weixin';
    }
}

