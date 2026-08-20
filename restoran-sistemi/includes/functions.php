<?php
/**
 * Ortak yardımcı fonksiyonlar
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/** HTML escape */
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** JSON çıktısı verip sonlandırır */
function json_out($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_ok($data = [], int $status = 200): void
{
    json_out(['success' => true, 'data' => $data], $status);
}

function json_err(string $message, int $status = 400): void
{
    json_out(['success' => false, 'message' => $message], $status);
}

/** BASE_URL ile yönlendirme */
function redirect(string $path): void
{
    $url = str_starts_with($path, 'http') ? $path : BASE_URL . $path;
    header('Location: ' . $url);
    exit;
}

function url(string $path): string
{
    return BASE_URL . $path;
}

function money($value): string
{
    return number_format((float)$value, 2, ',', '.') . ' ₺';
}

/** İstek gövdesini (JSON veya form) dizi olarak döndürür */
function request_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw !== false && $raw !== '') {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            return array_merge($_GET, $json);
        }
    }
    return array_merge($_GET, $_POST);
}

function input_str(array $in, string $key, string $default = ''): string
{
    return isset($in[$key]) ? trim((string)$in[$key]) : $default;
}

function input_int(array $in, string $key, int $default = 0): int
{
    return isset($in[$key]) && $in[$key] !== '' ? (int)$in[$key] : $default;
}

function input_float(array $in, string $key, float $default = 0.0): float
{
    if (!isset($in[$key]) || $in[$key] === '') {
        return $default;
    }
    return (float)str_replace(',', '.', (string)$in[$key]);
}

/** CSRF */
function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_check(?string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    return is_string($token) && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function require_csrf(array $in): void
{
    $token = $in['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    if (!csrf_check($token)) {
        json_err('Geçersiz güvenlik anahtarı (CSRF).', 403);
    }
}

function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        json_err('Bu endpoint yalnızca POST kabul eder.', 400);
    }
}

/** Audit log */
function audit_log(string $action, ?string $entityType = null, ?int $entityId = null, $oldData = null, $newData = null): void
{
    try {
        $stmt = db()->prepare(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_data, new_data, ip)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            function_exists('current_user') && current_user() ? current_user()['id'] : null,
            $action,
            $entityType,
            $entityId,
            $oldData === null ? null : json_encode($oldData, JSON_UNESCAPED_UNICODE),
            $newData === null ? null : json_encode($newData, JSON_UNESCAPED_UNICODE),
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Throwable $e) {
        // audit log hatası ana işlemi bozmamalı
        error_log('audit_log error: ' . $e->getMessage());
    }
}

function setting(string $key, ?string $default = null): ?string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->query('SELECT setting_key, setting_value FROM settings') as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $cache[$key] ?? $default;
}

/** Türkçe durum etiketleri */
function status_label(string $status): string
{
    return [
        'bos' => 'Boş', 'dolu' => 'Dolu', 'rezerve' => 'Rezerve',
        'hesap_bekliyor' => 'Hesap Bekliyor', 'kapali' => 'Kapalı',
        'draft' => 'Taslak', 'sent' => 'Mutfağa Gönderildi', 'preparing' => 'Hazırlanıyor',
        'ready' => 'Hazır', 'served' => 'Servis Edildi', 'cancelled' => 'İptal',
        'open' => 'Açık', 'paid' => 'Ödendi', 'merged' => 'Birleştirildi',
        'nakit' => 'Nakit', 'kredi_karti' => 'Kredi Kartı', 'yemek_karti' => 'Yemek Kartı',
        'havale' => 'Havale', 'diger' => 'Diğer',
    ][$status] ?? $status;
}
