<?php
require_once __DIR__ . '/_bootstrap.php';
require_role_api(['admin', 'yonetici', 'garson', 'kasa']);
require_post();
require_csrf($INPUT);

$sourceTableId = input_int($INPUT, 'source_table_id');
$targetTableId = input_int($INPUT, 'target_table_id');

if ($sourceTableId <= 0 || $targetTableId <= 0) json_err('Kaynak ve hedef masa seçilmelidir.', 422);
if ($sourceTableId === $targetTableId) json_err('Kaynak ve hedef masa aynı olamaz.', 422);

$pdo = db();
$pdo->beginTransaction();
try {
    $session = open_session_for_table($sourceTableId);
    if (!$session) throw new RuntimeException('Kaynak masada açık adisyon yok.');
    if (open_session_for_table($targetTableId)) throw new RuntimeException('Hedef masada açık adisyon var. Birleştirme kullanın.');

    $pdo->prepare('UPDATE table_sessions SET table_id = ? WHERE id = ?')->execute([$targetTableId, $session['id']]);
    $pdo->prepare('UPDATE cafe_tables SET status = "bos" WHERE id = ?')->execute([$sourceTableId]);
    $pdo->prepare('UPDATE cafe_tables SET status = "dolu" WHERE id = ?')->execute([$targetTableId]);

    audit_log('table_move', 'table_session', (int)$session['id'],
        ['table_id' => $sourceTableId], ['table_id' => $targetTableId]);

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    json_err($e->getMessage(), 422);
}

json_ok(['session_id' => (int)$session['id'], 'table_id' => $targetTableId]);
