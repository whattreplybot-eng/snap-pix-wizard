<?php
/**
 * Stok hareketleri & reçete bazlı otomatik düşüm.
 * Not: Çağıran tarafın transaction açmış olması beklenir.
 */
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/**
 * Stok hareketi kaydeder ve current_qty günceller.
 * $quantity pozitif (giriş) veya negatif (çıkış) olabilir.
 */
function stock_move(
    int $stockItemId,
    string $type,
    float $quantity,
    ?float $unitCost = null,
    ?string $referenceType = null,
    ?int $referenceId = null,
    ?string $reason = null,
    ?int $userId = null
): void {
    $allowed = ['purchase', 'sale', 'manual_in', 'manual_out', 'waste', 'count_adjustment'];
    if (!in_array($type, $allowed, true)) {
        throw new InvalidArgumentException('Geçersiz stok hareket tipi.');
    }

    $pdo = db();
    $pdo->prepare(
        'INSERT INTO stock_movements
         (stock_item_id, type, quantity, unit_cost, reference_type, reference_id, reason, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([$stockItemId, $type, $quantity, $unitCost, $referenceType, $referenceId, $reason, $userId]);

    $pdo->prepare('UPDATE stock_items SET current_qty = current_qty + ? WHERE id = ?')
        ->execute([$quantity, $stockItemId]);

    if ($unitCost !== null && $unitCost > 0 && $quantity > 0) {
        $pdo->prepare('UPDATE stock_items SET cost_price = ? WHERE id = ?')->execute([$unitCost, $stockItemId]);
    }
}

/** Ürünün aktif reçetesini döndürür */
function product_recipe_items(int $productId): array
{
    $stmt = db()->prepare(
        'SELECT ri.*, si.name, si.cost_price, si.unit AS stock_unit
         FROM recipes r
         JOIN recipe_items ri ON ri.recipe_id = r.id
         JOIN stock_items si ON si.id = ri.stock_item_id
         WHERE r.product_id = ? AND r.is_active = 1'
    );
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
}

/** Reçeteye göre ürün maliyeti */
function product_recipe_cost(int $productId): float
{
    $cost = 0.0;
    foreach (product_recipe_items($productId) as $ri) {
        $cost += (float)$ri['quantity'] * (float)$ri['cost_price'];
    }
    return round($cost, 2);
}

/** Satılan ürün için reçete bazlı stok düşümü */
function consume_stock_for_product(int $productId, float $qty, int $orderItemId, ?int $userId): void
{
    $items = product_recipe_items($productId);
    foreach ($items as $ri) {
        stock_move(
            (int)$ri['stock_item_id'],
            'sale',
            -1 * (float)$ri['quantity'] * $qty,
            null,
            'order_item',
            $orderItemId,
            'Satış: reçete düşümü',
            $userId
        );
    }
}

/** Kritik stoklar */
function critical_stock_items(): array
{
    return db()->query(
        'SELECT * FROM stock_items WHERE is_active = 1 AND current_qty <= min_qty ORDER BY current_qty ASC'
    )->fetchAll();
}
