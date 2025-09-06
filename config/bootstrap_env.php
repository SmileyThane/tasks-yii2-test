<?php
use Dotenv\Dotenv;

$root = dirname(__DIR__);

if (is_file($root.'/.env')) {
    $dotenv = Dotenv::createImmutable($root);
    $dotenv->safeLoad();
}

if (!defined('YII_ENV')) {
    define('YII_ENV', $_ENV['YII_ENV'] ?? 'prod');
}

if (!defined('YII_DEBUG')) {
    $debug = strtolower((string)($_ENV['YII_DEBUG'] ?? '0'));
    define('YII_DEBUG', in_array($debug, ['1','true','yes','on'], true));
}
