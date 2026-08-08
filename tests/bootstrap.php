<?php

require_once __DIR__ . '/../vendor/autoload.php';

// PHPUnit çalışırken .env dosyasını otomatik olarak $_ENV içerisine yükler
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
}
