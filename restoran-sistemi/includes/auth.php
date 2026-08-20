<?php
/**
 * Oturum & rol yönetimi
 */
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function current_user(): ?array
{
    static $user = null;
    if ($user !== null) {
        return $user;
    }
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $stmt = db()->prepare('SELECT id, full_name, username, role, discount_limit, is_active FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    if (!$row || (int)$row['is_active'] !== 1) {
        return null;
    }
    $user = $row;
    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function attempt_login(string $username, string $password): bool
{
    $stmt = db()->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || (int)$user['is_active'] !== 1) {
        return false;
    }
    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['role']    = $user['role'];
    audit_log('login', 'user', (int)$user['id']);
    return true;
}

function logout(): void
{
    if (is_logged_in()) {
        audit_log('logout', 'user', (int)current_user()['id']);
    }
    $_SESSION = [];
    session_destroy();
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('/login.php');
    }
}

/** @param string[] $roles */
function has_role(array $roles): bool
{
    $u = current_user();
    return $u !== null && in_array($u['role'], $roles, true);
}

function require_role(array $roles): void
{
    require_login();
    if (!has_role($roles)) {
        http_response_code(403);
        include __DIR__ . '/forbidden.php';
        exit;
    }
}

function require_login_api(): void
{
    if (!is_logged_in()) {
        json_err('Giriş yapmalısınız.', 401);
    }
}

function require_role_api(array $roles): void
{
    require_login_api();
    if (!has_role($roles)) {
        json_err('Bu işlem için yetkiniz yok.', 403);
    }
}

/** Rol için varsayılan açılış sayfası */
function home_for_role(string $role): string
{
    return match ($role) {
        'admin', 'yonetici' => '/admin/index.php',
        'garson'            => '/waiter/index.php',
        'kasa'              => '/cashier/index.php',
        'mutfak'            => '/kitchen/index.php',
        'depo'              => '/warehouse/index.php',
        default             => '/index.php',
    };
}

/** Rol bazlı ana menü */
function menu_for_role(string $role): array
{
    $admin = [
        ['Dashboard', '/admin/index.php'],
        ['Masalar', '/cashier/index.php'],
        ['Ürünler', '/admin/products.php'],
        ['Kategoriler', '/admin/categories.php'],
        ['Masa Yönetimi', '/admin/tables.php'],
        ['Reçeteler', '/admin/recipes.php'],
        ['Stok', '/warehouse/stock.php'],
        ['Depo', '/warehouse/index.php'],
        ['Tedarikçiler', '/warehouse/suppliers.php'],
        ['Satın Almalar', '/warehouse/purchases.php'],
        ['Personel', '/admin/users.php'],
        ['Raporlar', '/admin/reports.php'],
        ['Kasa', '/cashier/closing.php'],
        ['Audit Log', '/admin/audit.php'],
        ['Ayarlar', '/admin/settings.php'],
    ];

    return match ($role) {
        'admin'    => $admin,
        'yonetici' => array_values(array_filter($admin, fn($i) => !in_array($i[1], ['/admin/settings.php', '/admin/users.php'], true))),
        'garson'   => [['Masalar', '/waiter/index.php'], ['Siparişler', '/waiter/orders.php']],
        'mutfak'   => [['Mutfak', '/kitchen/index.php']],
        'kasa'     => [['Masalar', '/cashier/index.php'], ['Kasa', '/cashier/closing.php']],
        'depo'     => [
            ['Stok', '/warehouse/stock.php'],
            ['Stok Hareketleri', '/warehouse/movements.php'],
            ['Satın Almalar', '/warehouse/purchases.php'],
            ['Tedarikçiler', '/warehouse/suppliers.php'],
        ],
        default    => [],
    };
}
