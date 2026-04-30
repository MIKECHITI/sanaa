<?php
// ============================================================
// mpesa/callback.php — Receives Safaricom payment result
// URL: https://YOUR-NGROK.ngrok-free.app/sanaa/mpesa/callback.php
// ============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/token.php'; // for logMpesa()

// Step 1: Read raw body
$rawBody = file_get_contents('php://input');

// Step 2: Log IMMEDIATELY before any other code
logMpesa('callback_raw', $rawBody ?: 'EMPTY BODY');

// Step 3: Respond to Safaricom RIGHT NOW (must be within 5 seconds)
header('Content-Type: application/json');
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
if (ob_get_level()) ob_end_flush();
flush();

// Step 4: Parse
$data = json_decode($rawBody, true);
if (empty($data)) { logMpesa('callback_error', 'Cannot parse JSON'); exit; }

$stk = $data['Body']['stkCallback'] ?? null;
if (!$stk) { logMpesa('callback_error', 'Missing Body.stkCallback'); exit; }

$resultCode = (int)($stk['ResultCode'] ?? -1);
$resultDesc = $stk['ResultDesc']       ?? 'Unknown';
$checkoutId = $stk['CheckoutRequestID'] ?? '';

logMpesa('callback_parsed', "Code={$resultCode} | Checkout={$checkoutId} | Desc={$resultDesc}");

// Step 5: Handle success
if ($resultCode === 0) {
    $meta = [];
    foreach ($stk['CallbackMetadata']['Item'] ?? [] as $item) {
        $meta[$item['Name']] = $item['Value'] ?? null;
    }
    $receipt = $meta['MpesaReceiptNumber'] ?? null;
    $amount  = $meta['Amount']             ?? null;
    $phone   = $meta['PhoneNumber']        ?? null;

    logMpesa('callback_success', "Receipt={$receipt} | Amount={$amount} | Phone={$phone}");

    $pdo = db();

    // Update transaction record
    $pdo->prepare("
        UPDATE mpesa_transactions
        SET status='completed', result_code=?, result_desc=?,
            mpesa_receipt=?, completed_at=NOW()
        WHERE checkout_request_id=?
    ")->execute([$resultCode, $resultDesc, $receipt, $checkoutId]);

    // Get order ID from transaction
    $tx = $pdo->prepare('SELECT order_id FROM mpesa_transactions WHERE checkout_request_id=?');
    $tx->execute([$checkoutId]);
    $row = $tx->fetch();

    if ($row) {
        $orderId = $row['order_id'];

        // Mark order as paid
        $pdo->prepare("UPDATE orders SET status='paid', updated_at=NOW() WHERE id=?")
            ->execute([$orderId]);

        // Release artisan earnings
        $pdo->prepare("
            UPDATE artisan_earnings ae
            JOIN order_items oi ON oi.id = ae.order_item_id
            SET ae.status = 'available'
            WHERE oi.order_id = ?
        ")->execute([$orderId]);

        // Reduce stock
        $items = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id=?");
        $items->execute([$orderId]);
        foreach ($items->fetchAll() as $item) {
            $pdo->prepare("UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id=?")
                ->execute([$item['quantity'], $item['product_id']]);
        }

        logMpesa('callback_order_updated', "Order #{$orderId} marked paid. Receipt={$receipt}");
    }

} else {
    // Step 5b: Handle failure
    logMpesa('callback_failed', "Code={$resultCode} | Desc={$resultDesc} | Checkout={$checkoutId}");

    $status = in_array($resultCode, [1032, 1037]) ? 'cancelled' : 'failed';

    db()->prepare("
        UPDATE mpesa_transactions
        SET status=?, result_code=?, result_desc=?, completed_at=NOW()
        WHERE checkout_request_id=?
    ")->execute([$status, $resultCode, $resultDesc, $checkoutId]);
}
