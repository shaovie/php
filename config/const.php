<?php
/**
 * @Author shaowei
 * @Date   2015-09-17
 * 
 * 注意：优先使用const定义常量
 *       const是语言结构 define是函数，const编译比define快很多
 */

//= defines
define('EDITION',               isset($_SERVER['EDITION']) ? $_SERVER['EDITION'] : 'online');
define('ROOT_PATH',             realpath(__DIR__ . '/../'));
define('SRC_PATH',              ROOT_PATH . '/src');
define('LOG_DIR',               ROOT_PATH . '/logs');
define('LIBS_DIR',              ROOT_PATH . '/libs');
define('CONFIG_PATH',           ROOT_PATH . '/config');
define('PUBLIC_PATH',           ROOT_PATH . '/public');

define('CURRENT_TIME',          $_SERVER['REQUEST_TIME']); // 不敏感的时间可以取这个值
define('APP_HOST',              $_SERVER['APP_HOST']);

//= for cookie
define('COOKIE_PREFIX',         $_SERVER['COOKIE_PREFIX']);
define('COOKIE_DOMAIN',         $_SERVER['COOKIE_DOMAIN']);

define('APP_URL_BASE',          'http://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : APP_HOST));

define('SELF_IP',               $_SERVER['SELF_IP']);

//= for mysql
// dsn=mysql:host=127.0.0.1;port=3306;dbname=user;charset=utf8
define('DB_R_DSN',              $_SERVER['DB_R_DSN']);
define('DB_R_USER',             $_SERVER['DB_R_USER']);
define('DB_R_PASSWD',           $_SERVER['DB_R_PASSWD']);

define('DB_W_DSN',              $_SERVER['DB_W_DSN']);
define('DB_W_USER',             $_SERVER['DB_W_USER']);
define('DB_W_PASSWD',           $_SERVER['DB_W_PASSWD']);

//= for redis
define('CACHE_PREFIX',          $_SERVER['CACHE_PREFIX']);
define('REDIS_CACHE_HOST',      $_SERVER['REDIS_CACHE_HOST']);
define('REDIS_CACHE_PORT',      $_SERVER['REDIS_CACHE_PORT']);
define('NOSQL_PREFIX',          $_SERVER['NOSQL_PREFIX']);
define('REDIS_NOSQL_HOST',      $_SERVER['REDIS_NOSQL_HOST']);
define('REDIS_NOSQL_PORT',      $_SERVER['REDIS_NOSQL_PORT']);

//= weixin config
const MAX_KF_MSG_LENGTH         = 2048;      // 微信客服消息最大允许长度
define('WX_APP_ID',             $_SERVER['WX_APP_ID']);
define('WX_APP_SECRET',         $_SERVER['WX_APP_SECRET']);

// 微信公众号支付信息
define('WX_PAY_APP_ID',         $_SERVER['WX_PAY_APP_ID']);
define('WX_PAY_APP_SECRET',     $_SERVER['WX_PAY_APP_SECRET']);
define('WX_PAY_MCHID',          $_SERVER['WX_PAY_MCHID']);
define('WX_PAY_KEY',            $_SERVER['WX_PAY_KEY']);

//= alipay config
const ALI_PAY_PARTNER_ID        = '2088411445174760';
