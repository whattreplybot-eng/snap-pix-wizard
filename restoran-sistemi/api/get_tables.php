<?php
require_once __DIR__ . '/_bootstrap.php';
require_login_api();

$rows = db()->query(
    'SELECT t.*, ts.id AS session_id, ts.opened_at, ts.customer_count, u.full_name AS waiter_name
     FROM cafe_tables t
     LEFT JOIN table_sessions ts ON ts.table_id = t.id AND ts.status = "open"
     LEFT JOIN users u ON u.id = ts.opened_by
     WHERE t.is_active = 1
     ORDER BY t.section, t.sort_order, t.id'
)->fetchAll();

$hideMoney = has_role(['mutfak']);

$tables = [];
foreach ($rows as $row) {
    $total = 0.0;
    if (!empty($row['session_id']) && !$hideMoney) {
        $bill = calculate_bill((int)$row['session_id']);
        $total = $bill['total'];
        $row['remaining'] = $bill['remaining'];
    }
    $row['total'] = $total;
    $tables[] = $row;
}

$sections = [];
foreach ($tables as $t) {
    $sections[$t['section']][] = $t;
}

json_ok(['tables' => $tables, 'sections' => $sections]);
