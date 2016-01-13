<?php
/**
 * @Author shaowei
 * @Date   2015-07-17
 */

require __DIR__ . '/../config/const.php';

spl_autoload_register(function ($cls) {
    $cls = str_replace('\\', '/', $cls);
    require ROOT_PATH . DIRECTORY_SEPARATOR . $cls . '.php';
});

set_exception_handler(function ($e) {
    file_put_contents(
        LOG_DIR . '/exception.log',
        date('Y-m-d H:i:s') . ' - ' . $e . PHP_EOL . PHP_EOL,
        FILE_APPEND
    );
});

// example: '/module/class/function?p1=v1&p2=v2
$uri = explode('?', $_SERVER['REQUEST_URI']);
$uri = explode('/', $uri[0]);
$module = empty($uri[1]) ? 'mall'  : $uri[1];
$cls    = empty($uri[2]) ? 'Home'   : $uri[2];
$func   = empty($uri[3]) ? 'index'  : $uri[3];

$cls = 'src\\' . $module . '\\controller\\' . $cls . 'Controller';
if (!class_exists($cls) || !method_exists($cls, $func)) {
    header('HTTP/1.1 404 Not Found');
    exit('404 Not Found');
}
(new $cls())->$func();

