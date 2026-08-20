<?php
require_once __DIR__ . '/_bootstrap.php';
require_role_api(['admin', 'yonetici']);
require_post();
require_csrf($INPUT);

$name = input_str($INPUT, 'name');
$section = input_str($INPUT, 'section', 'İç Alan');
$capacity = input_int($INPUT, 'capacity', 4);
$sort = input_int($INPUT, 'sort_order', 0);

if ($name === '') json_err('Masa adı zorunludur.', 422);
if ($capacity < 1) json_err('Kapasite en az 1 olmalıdır.', 422);

$token = 'qr-' . bin2hex(random_bytes(8));
db()->prepare('INSERT INTO cafe_tables (name, section, capacity, sort_order, qr_token) VALUES (?,?,?,?,?)')
   ->execute([$name, $section, $capacity, $sort, $token]);
$id = (int)db()->lastInsertId();
audit_log('table_create', 'cafe_table', $id, null, ['name' => $name, 'section' => $section]);

json_ok(['id' => $id, 'qr_token' => $token], 201);
