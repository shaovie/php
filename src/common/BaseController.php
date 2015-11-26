<?php
/**
 * @Author shaowei
 * @Date   2015-07-18
 */

namespace src\common;

class BaseController
{
    protected $module = '';

    public function __construct()
    {
    }

    // 获取HTTP GET的参数
    protected function getParam($key, $defaultVal)
    {
        if (empty($key)) {
            return $defaultVal;
        }
        return isset($_GET[$key]) ? $_GET[$key] : $defaultVal;
    }

    // 获取HTTP POST的参数
    protected function postParam($key, $defaultVal)
    {
        if (empty($key)) {
            return $defaultVal;
        }
        return isset($_POST[$key]) ? $_POST[$key] : $defaultVal;
    }

    // 模板引用模块
    final public function display($tplName, $data = array(), $isAbsoPath = false)
    {
        if (empty($tplName)) {
            return ;
        }

        if (!empty($data)) {
            extract($data);
        }

        if ($isAbsoPath) {
            $file = $tplName;
        } else {
            $file = SRC_PATH . '/' . $this->module . '/view/' . $tplName . '.php';
        }

        try {
            include $file;
        } catch (\Exception $e) {
            Log::error('template - file=' . $file . ' ' . $e->getMessage());
        }
        return ;
    }
}

