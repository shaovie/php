<?php
/**
 * @Author shaowei
 * @Date   2015-07-17
 */

if (php_sapi_name() != 'cli') {
    echo 'error';
    exit();
}

$_SERVER['REQUEST_URI'] = $argv[2];
$fp = fopen($argv[1], 'r');
$fp or die('not found ' . $argv[1]);
while (!feof($fp)) {
    $line = trim(stream_get_line($fp, 1024, PHP_EOL));
    if (strlen($line) > 0 && $line[0] != '#') {
        $params = preg_split("/[\s;]+/", $line);
        if (count($params) == 4) {
            $_SERVER[$params[1]] = $params[2];
        }
    }
}
fclose($fp);

require __DIR__ . '/index.php';

