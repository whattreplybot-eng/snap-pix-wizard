<?php
/** Tüm API endpointleri için ortak başlangıç */
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/billing.php';
require_once __DIR__ . '/../includes/stock.php';

header('Content-Type: application/json; charset=utf-8');

set_exception_handler(function (Throwable $e) {
    error_log('API error: ' . $e->getMessage());
    json_err(APP_DEBUG ? $e->getMessage() : 'Sunucu hatası oluştu.', 500);
});

$INPUT = request_input();
