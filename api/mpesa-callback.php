<?php
require_once __DIR__ . '/../includes/config.php';

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

// CRITICAL: Log everything for debugging
file_put_contents(__DIR__ . '/../mpesa_callback.log', 
    date('Y-m-d H:i:s') . " - RAW: " . $raw . "\n\n", FILE_APPEND);

if (!$data || !isset($data['Body']['stkCallback'])) {
    http_response_code(400);
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid payload']);
    exit;
}

$body = $data['Body']['stkCallback'];
$checkoutId = $body['CheckoutRequestID'] ?? '';
$resultCode = (int)($body['ResultCode'] ?? -1);
$resultDesc = $body['ResultDesc'] ?? '';

file_put_contents(__DIR__ . '/../mpesa_callback.log', 
    "CheckoutID: $checkoutId | ResultCode: $resultCode | Desc: $resultDesc\n", FILE_APPEND);    

$pdo = db();

if ($resultCode === 0) {
    // Payment successful — extract metadata
    $items  = $body['CallbackMetadata']['Item'] ?? [];
    $meta   = [];
    foreach ($items as $item) {
        $meta[$item['Name']] = $item['Value'] ?? '';
    }
    $receipt = $meta['MpesaReceiptNumber'] ?? '';
    $amount  = $meta['Amount'] ?? 0;
    $phone   = $meta['PhoneNumber'] ?? '';

    // Update transaction record
    $pdo->prepare("
        UPDATE mpesa_transactions
        SET status = 'completed', result_code = ?, result_desc = ?,
            mpesa_receipt = ?, completed_at = NOW()
        WHERE checkout_request_id = ?
    ")->execute([$resultCode, $resultDesc, $receipt, $checkoutId]);

    // Update order status
    $tx = $pdo->prepare('SELECT order_id FROM mpesa_transactions WHERE checkout_request_id = ?');
    $tx->execute([$checkoutId]);
    $row = $tx->fetch();
    if ($row) {
        $pdo->prepare("UPDATE orders SET status = 'paid', updated_at = NOW() WHERE id = ?")
            ->execute([$row['order_id']]);

        // Mark artisan earnings as available
        $pdo->prepare("
            UPDATE artisan_earnings ae
            JOIN order_items oi ON oi.id = ae.order_item_id
            SET ae.status = 'available'
            WHERE oi.order_id = ?
        ")->execute([$row['order_id']]);
    }

} else {
    // Payment failed or cancelled
    $status = in_array($resultCode, [1032,1037]) ? 'cancelled' : 'failed';
    $pdo->prepare("
        UPDATE mpesa_transactions
        SET status = ?, result_code = ?, result_desc = ?, completed_at = NOW()
        WHERE checkout_request_id = ?
    ")->execute([$status, $resultCode, $resultDesc, $checkoutId]);
}

// Acknowledge Safaricom
http_response_code(200);
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
exit;
