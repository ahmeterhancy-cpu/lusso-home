<?php

/*
 |  PHP'nin yerleşik sunucusu için yönlendirici.
 |  Gerçek dosya varsa onu sun, yoksa isteği Laravel'e devret.
 |  Kullanım: php -S 127.0.0.1:8191 -t public server.php
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

require_once __DIR__.'/public/index.php';
