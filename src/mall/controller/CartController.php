<?php
/**
 * @Author shaowei
 * @Date   2015-12-23
 */

namespace src\mall\controller;

use \src\common\Check;
use \src\mall\model\CartModel;
use \src\mall\model\GoodsSKUModel;
use \src\user\model\UserCartModel;

class CartController extends MallController
{
    public function __construct()
    {
        parent::__construct();

        $this->checkLoginAndNotice();
    }

    public function index()
    {
        $cartList = CartModel::getCartList($this->userId());
        $this->display('');
    }
}

