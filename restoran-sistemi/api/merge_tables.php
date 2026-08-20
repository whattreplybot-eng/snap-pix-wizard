<?php
require_once __DIR__ . '/_bootstrap.php';
require_role_api(['admin', 'yonetici', 'kasa']);
require_post();
require_csrf($INPUT);

$sourceTableId = input_int($INPUT, 'source_table_id');
$targetTableId = input_int($INPUT, 'target_table_id');

if ($sourceTableId <= 0 || $targetTableId <= 0) json_err('Kaynak ve hedef masa seçilmelidir.', 422);
if ($sourceTableId === $targetTableId) json_err('Kaynak ve hedef masa aynı olamaz.', 422);

$pdo = db();
$pdo->beginTransaction();
try {
    $source = open_session_for_table($sourceTableId);
    $target = open_session_for_table($targetTableId);
    if (!$source) throw new RuntimeException('Kaynak masada açık adisyon yok.');
    if (!$target) throw new RuntimeException('Hedef masada açık adisyon yok.');

    $pdo->prepare('UPDATE orders SET session_id = ? WHERE session_id = ?')->execute([$target['id'], $source['id']]);
    $pdo->prepare('UPDATE payments SET session_id = ? WHERE session_id = ?')->execute([$target['id'], $source['id']]);
    $pdo->prepare('UPDATE table_sessions SET status = "merged", merged_into_id = ?, closed_at = NOW() WHERE id = ?')
        ->execute([$target['id'], $source['id']]);
    $pdo->prepare('UPDATE cafe_tables SET status = "bos" WHERE id = ?')->execute([$sourceTableId]);

    audit_log('table_merge', 'table_session', (int)$source['id'],
        ['source_session' => (int)$source['id']], ['target_session' => (int)$target['id']]);

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    json_err($e->getMessage(), 422);
}

json_ok(calculate_bill((int)$target['id']));
