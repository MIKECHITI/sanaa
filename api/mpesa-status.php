<?php
// ============================================================
// api/mpesa-status.php — Polled by payment-pending.php
// Returns the current payment status for a given order.
// Called every 5 seconds by the frontend JS.
// ============================================================
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

$orderId = cleanInt($_GET['order'] ?? 0);

if (!$orderId) {
    echo json_encode(['status' => 'invalid', 'desc' => 'No order ID provided.']);
    exit;
}

// Fetch latest transaction for this order
$stmt = db()->prepare("
    SELECT mt.status, mt.result_desc, mt.mpesa_receipt,
           o.status AS order_status
    FROM mpesa_transactions mt
    JOIN orders o ON o.id = mt.order_id
    WHERE mt.order_id = ?
    ORDER BY mt.initiated_at DESC
    LIMIT 1
");
$stmt->execute([$orderId]);
$row = $stmt->fetch();

if (!$row) {
    echo json_encode(['status' => 'pending', 'desc' => 'Waiting for payment...']);
    exit;
}

echo json_encode([
    'status'  => $row['status'],         // pending | completed | failed | cancelled
    'receipt' => $row['mpesa_receipt'],  // e.g. QHJ29KL4TY
    'desc'    => $row['result_desc'],    // human-readable Safaricom message
    'order'   => $row['order_status'],   // pending | paid | cancelled
]);
