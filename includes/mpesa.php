<?php
// ============================================================
// includes/mpesa.php  — Safaricom Daraja API (STK Push)
// ============================================================
require_once __DIR__ . '/config.php';

class Mpesa {

    private static function isSimulated(): bool {
        return defined('MPESA_SIMULATED') && MPESA_SIMULATED;
    }

    // ── Get OAuth access token ────────────────────────────────
    public static function getToken(): string {
        if (self::isSimulated()) {
            return 'SIMULATED_TOKEN';
        }

        $credentials = base64_encode(MPESA_CONSUMER_KEY . ':' . MPESA_CONSUMER_SECRET);

        $ch = curl_init(MPESA_TOKEN_URL);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => ['Authorization: Basic ' . $credentials],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        return $data['access_token'] ?? '';
    }

    // ── Initiate STK Push ────────────────────────────────────
    public static function stkPush(int $orderId, string $phone, float $amount): array {
        if (self::isSimulated()) {
            $phone = self::normalizePhone($phone);
            $checkoutId = 'SIM' . time() . mt_rand(1000, 9999);
            $merchantId = 'SIM' . mt_rand(100000, 999999);
            $receipt    = 'SIM' . mt_rand(1000000, 9999999);

            $pdo = db();
            $pdo->prepare("INSERT INTO mpesa_transactions 
                (order_id, phone, amount, merchant_request_id, checkout_request_id, mpesa_receipt, result_code, result_desc, status, completed_at)
                VALUES (?,?,?,?,?,?,?,?,?, NOW())")
                ->execute([
                    $orderId, $phone, $amount,
                    $merchantId, $checkoutId, $receipt,
                    '0', 'Simulated payment accepted.', 'completed'
                ]);

            $pdo->prepare("UPDATE orders SET status = 'paid', updated_at = NOW() WHERE id = ?")
                ->execute([$orderId]);
            $pdo->prepare("UPDATE artisan_earnings ae JOIN order_items oi ON oi.id = ae.order_item_id
                SET ae.status = 'available' WHERE oi.order_id = ?")
                ->execute([$orderId]);

            return [
                'MerchantRequestID'   => $merchantId,
                'CheckoutRequestID'   => $checkoutId,
                'ResponseCode'        => '0',
                'ResponseDescription' => 'Simulated STK Push successful',
                'CustomerMessage'     => 'Demo payment completed successfully.',
            ];
        }

        $token = self::getToken();
        if (empty($token)) {
            error_log("MPESA: Failed to get access token");
            return ['error' => 'Failed to get access token'];
        }

    $timestamp = date('YmdHis');
    $password  = base64_encode(MPESA_SHORTCODE . MPESA_PASSKEY . $timestamp);
    $phone     = self::normalizePhone($phone);

    $payload = [
        'BusinessShortCode' => MPESA_SHORTCODE,
        'Password'          => $password,
        'Timestamp'         => $timestamp,
        'TransactionType'   => 'CustomerPayBillOnline',
        'Amount'            => (int) ceil($amount),
        'PartyA'            => $phone,
        'PartyB'            => MPESA_SHORTCODE,
        'PhoneNumber'       => $phone,
        'CallBackURL'       => MPESA_CALLBACK_URL,
        'AccountReference'  => 'Order#' . $orderId,
        'TransactionDesc'   => 'Sanaa Ya Kenya Payment',
    ];

    $ch = curl_init(MPESA_STK_URL);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,  // Set true in production
        CURLOPT_TIMEOUT        => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    error_log("MPESA STK Push Response (HTTP $httpCode): " . $response);

    $result = json_decode($response, true) ?? [];

    // Save to DB even on error for debugging
    $pdo = db();
    $pdo->prepare("INSERT INTO mpesa_transactions 
        (order_id, phone, amount, merchant_request_id, checkout_request_id, status, result_desc)
        VALUES (?, ?, ?, ?, ?, 'pending', ?)")
        ->execute([
            $orderId, $phone, $amount,
            $result['MerchantRequestID'] ?? '',
            $result['CheckoutRequestID'] ?? '',
            $result['errorMessage'] ?? json_encode($result)
        ]);

    return $result;
}
    // ── Query transaction status ─────────────────────────────
    public static function queryStatus(string $checkoutRequestId): array {
        $token     = self::getToken();
        $timestamp = date('YmdHis');
        $password  = base64_encode(MPESA_SHORTCODE . MPESA_PASSKEY . $timestamp);

        $payload = [
            'BusinessShortCode' => MPESA_SHORTCODE,
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId,
        ];

        $ch = curl_init('https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true) ?? [];
    }

    // ── Phone normalizer (07xx → 2547xx) ─────────────────────
    public static function normalizePhone(string $phone): string {
        $phone = preg_replace('/\D/', '', $phone);
        if (str_starts_with($phone, '0'))  $phone = '254' . substr($phone, 1);
        if (str_starts_with($phone, '+'))  $phone = substr($phone, 1);
        return $phone;
    }
}
