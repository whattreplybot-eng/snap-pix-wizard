<?php
/**
 * Merkezi yapılandırma + PDO bağlantısı
 */
declare(strict_types=1);

if (!defined('BASE_URL')) {
    define('BASE_URL', '/restoran-sistemi');
}

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'kafe_yonetim');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Üretimde false yapın.
define('APP_DEBUG', true);

date_default_timezone_set('Europe/Istanbul');

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $pdo->exec("SET time_zone = '+03:00'");
    } catch (PDOException $e) {
        http_response_code(500);
        if (APP_DEBUG) {
            die('Veritabanı bağlantı hatası: ' . $e->getMessage());
        }
        die('Veritabanına bağlanılamadı.');
    }

    return $pdo;
}
