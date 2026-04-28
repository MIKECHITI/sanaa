<?php
// ============================================================
// api/mpesa-status.php  — Polled by payment-pending.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

$orderId = cleanInt($_GET['order'] ?? 0);
if (!$orderId) { echo json_encode(['status'=>'error']); exit; }

$st = db()->prepare("
    SELECT mt.status, mt.mpesa_receipt, mt.result_desc, o.status AS order_status
    FROM mpesa_transactions mt
    JOIN orders o ON o.id = mt.order_id
    WHERE mt.order_id = ?
    ORDER BY mt.initiated_at DESC LIMIT 1
");
$st->execute([$orderId]);
$row = $st->fetch();

if (!$row) { echo json_encode(['status'=>'pending']); exit; }

echo json_encode([
    'status'  => $row['status'],
    'receipt' => $row['mpesa_receipt'],
    'desc'    => $row['result_desc'],
]);
