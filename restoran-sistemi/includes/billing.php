<?php
/**
 * Adisyon hesaplama & ödeme yardımcıları.
 * Tüm finansal hesaplar BACKEND'de yapılır; frontend'den gelen tutara güvenilmez.
 */
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/** Bir session'ın tüm kalemlerini döndürür */
function session_items(int $sessionId): array
{
    $stmt = db()->prepare(
        'SELECT oi.*, p.name AS product_name, p.cost_price, o.waiter_id
         FROM order_items oi
         JOIN orders o ON o.id = oi.order_id
         JOIN products p ON p.id = oi.product_id
         WHERE o.session_id = ? AND oi.status <> "cancelled"
         ORDER BY oi.id'
    );
    $stmt->execute([$sessionId]);
    return $stmt->fetchAll();
}

/** Adisyon toplamlarını hesaplar */
function calculate_bill(int $sessionId): array
{
    $stmt = db()->prepare(
        'SELECT ts.*, t.name AS table_name, t.section, u.full_name AS opened_by_name
         FROM table_sessions ts
         JOIN cafe_tables t ON t.id = ts.table_id
         LEFT JOIN users u ON u.id = ts.opened_by
         WHERE ts.id = ?'
    );
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch();
    if (!$session) {
        throw new RuntimeException('Adisyon bulunamadı.');
    }

    $items = session_items($sessionId);

    $subtotal = 0.0;
    $itemDiscounts = 0.0;
    $complimentary = 0.0;
    $cost = 0.0;

    foreach ($items as &$item) {
        $gross = (float)$item['qty'] * (float)$item['unit_price'];
        $lineDiscount = (float)$item['discount_amount'];
        if ((int)$item['is_complimentary'] === 1) {
            $complimentary += $gross;
            $lineDiscount = $gross;
        }
        $lineTotal = max(0.0, $gross - $lineDiscount);
        $item['line_gross'] = round($gross, 2);
        $item['line_total'] = round($lineTotal, 2);
        $subtotal += $gross;
        $itemDiscounts += $lineDiscount;
        $cost += (float)$item['qty'] * (float)$item['cost_price'];
    }
    unset($item);

    $afterItems = max(0.0, $subtotal - $itemDiscounts);

    $sessionDiscount = 0.0;
    if ($session['discount_type'] === 'percentage') {
        $sessionDiscount = $afterItems * ((float)$session['discount_value'] / 100);
    } elseif ($session['discount_type'] === 'fixed') {
        $sessionDiscount = min($afterItems, (float)$session['discount_value']);
    }

    $total = round(max(0.0, $afterItems - $sessionDiscount), 2);

    $pstmt = db()->prepare('SELECT p.*, u.full_name AS received_by_name FROM payments p
                            LEFT JOIN users u ON u.id = p.received_by
                            WHERE p.session_id = ? ORDER BY p.id');
    $pstmt->execute([$sessionId]);
    $payments = $pstmt->fetchAll();

    $paid = 0.0;
    foreach ($payments as $p) {
        $paid += (float)$p['amount'];
    }

    return [
        'session'           => $session,
        'items'             => $items,
        'payments'          => $payments,
        'subtotal'          => round($subtotal, 2),
        'item_discounts'    => round($itemDiscounts, 2),
        'complimentary'     => round($complimentary, 2),
        'session_discount'  => round($sessionDiscount, 2),
        'total'             => $total,
        'paid'              => round($paid, 2),
        'remaining'         => round(max(0.0, $total - $paid), 2),
        'cost'              => round($cost, 2),
        'gross_profit'      => round($total - $cost, 2),
    ];
}

/** Masanın açık session'ını döndürür (yoksa null) */
function open_session_for_table(int $tableId): ?array
{
    $stmt = db()->prepare('SELECT * FROM table_sessions WHERE table_id = ? AND status = "open" LIMIT 1');
    $stmt->execute([$tableId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** Masa açık session'ı yoksa oluşturur */
function ensure_session_for_table(int $tableId, int $userId, int $customerCount = 1): array
{
    $existing = open_session_for_table($tableId);
    if ($existing) {
        return $existing;
    }
    $pdo = db();
    $stmt = $pdo->prepare('INSERT INTO table_sessions (table_id, opened_by, customer_count) VALUES (?, ?, ?)');
    $stmt->execute([$tableId, $userId, max(1, $customerCount)]);
    $id = (int)$pdo->lastInsertId();
    $pdo->prepare('UPDATE cafe_tables SET status = "dolu" WHERE id = ?')->execute([$tableId]);
    audit_log('session_open', 'table_session', $id, null, ['table_id' => $tableId]);
    return open_session_for_table($tableId) ?? ['id' => $id, 'table_id' => $tableId];
}

/** Session'ın aktif (draft) siparişini döndürür/oluşturur */
function ensure_draft_order(int $sessionId, int $waiterId): int
{
    $stmt = db()->prepare('SELECT id FROM orders WHERE session_id = ? AND status = "draft" ORDER BY id DESC LIMIT 1');
    $stmt->execute([$sessionId]);
    $id = $stmt->fetchColumn();
    if ($id) {
        return (int)$id;
    }
    $ins = db()->prepare('INSERT INTO orders (session_id, waiter_id, status) VALUES (?, ?, "draft")');
    $ins->execute([$sessionId, $waiterId]);
    return (int)db()->lastInsertId();
}
