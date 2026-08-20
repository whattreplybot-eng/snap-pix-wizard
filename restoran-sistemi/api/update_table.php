<?php
require_once __DIR__ . '/_bootstrap.php';
require_role_api(['admin', 'yonetici']);
require_post();
require_csrf($INPUT);

$id = input_int($INPUT, 'id');
if ($id <= 0) json_err('Masa seçilmedi.', 422);

$stmt = db()->prepare('SELECT * FROM cafe_tables WHERE id = ?');
$stmt->execute([$id]);
$old = $stmt->fetch();
if (!$old) json_err('Masa bulunamadı.', 404);

$name = input_str($INPUT, 'name', $old['name']);
$section = input_str($INPUT, 'section', $old['section']);
$capacity = input_int($INPUT, 'capacity', (int)$old['capacity']);
$sort = input_int($INPUT, 'sort_order', (int)$old['sort_order']);
$status = input_str($INPUT, 'status', $old['status']);
$isActive = input_int($INPUT, 'is_active', (int)$old['is_active']);

$validStatus = ['bos','dolu','rezerve','hesap_bekliyor','kapali'];
if (!in_array($status, $validStatus, true)) json_err('Geçersiz masa durumu.', 422);
if ($name === '') json_err('Masa adı zorunludur.', 422);

db()->prepare('UPDATE cafe_tables SET name=?, section=?, capacity=?, sort_order=?, status=?, is_active=? WHERE id=?')
   ->execute([$name, $section, $capacity, $sort, $status, $isActive ? 1 : 0, $id]);
audit_log('table_update', 'cafe_table', $id, $old, $INPUT);

json_ok(['id' => $id]);
