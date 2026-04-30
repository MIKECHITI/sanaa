<?php
// ============================================================
// includes/mpesa.php — Mpesa class used by checkout.php
// ============================================================
require_once __DIR__ . '/../mpesa/token.php';

class Mpesa
{
    // ── Normalize phone to 2547XXXXXXXX ──────────────────────
    public static function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);          // strip non-digits
        if (str_starts_with($phone, '0'))                   // 07... → 2547...
            $phone = '254' . substr($phone, 1);
        if (str_starts_with($phone, '+'))                   // remove leading +
            $phone = substr($phone, 1);
        return $phone;
    }

    public static function isValidPhone(string $phone): bool
    {
        return preg_match('/^(?:254|\+254|0)(7|1)\d{8}$/', trim($phone)) === 1;
    }

    // ── Initiate STK Push ─────────────────────────────────────
    public static function stkPush(int $orderId, string $phone, float $amount): array
    {
        // ── Simulation mode (offline demo only) ───────────────
        if (defined('MPESA_SIMULATED') && MPESA_SIMULATED) {
            $checkoutId = 'SIM' . time() . mt_rand(1000, 9999);
            $receipt    = 'SIM' . strtoupper(bin2hex(random_bytes(4)));

            db()->prepare("
                INSERT INTO mpesa_transactions
                    (order_id, phone, amount, merchant_request_id,
                     checkout_request_id, mpesa_receipt, result_code,
                     result_desc, status, completed_at)
                VALUES (?,?,?,?,?,?,?,?,?, NOW())
            ")->execute([
                $orderId, self::normalizePhone($phone), $amount,
                'SIM-MID-' . $orderId, $checkoutId, $receipt,
                '0', 'Simulated payment accepted.', 'completed'
            ]);

            db()->prepare("UPDATE orders SET status='paid', updated_at=NOW() WHERE id=?")
               ->execute([$orderId]);

            db()->prepare("
                UPDATE artisan_earnings ae
                JOIN order_items oi ON oi.id = ae.order_item_id
                SET ae.status = 'available'
                WHERE oi.order_id = ?
            ")->execute([$orderId]);

            logMpesa('stk_simulated', "Order={$orderId} Checkout={$checkoutId}");
            return [
                'ResponseCode'        => '0',
                'CheckoutRequestID'   => $checkoutId,
                'MerchantRequestID'   => 'SIM-MID-' . $orderId,
                'ResponseDescription' => 'Simulated success',
                'CustomerMessage'     => 'Demo payment completed.',
            ];
        }

        // ── Real STK Push ─────────────────────────────────────
        if (!self::isValidPhone($phone)) {
            return ['error' => 'Invalid phone. Use format 07XXXXXXXX or 254XXXXXXXXX.'];
        }

        try {
            $token = getMpesaToken();
        } catch (RuntimeException $e) {
            logMpesa('stk_token_error', $e->getMessage());
            return ['error' => 'Could not connect to M-Pesa: ' . $e->getMessage()];
        }

        $phone     = self::normalizePhone($phone);
        $timestamp = date('YmdHis');
        $password  = base64_encode(SHORTCODE . PASSKEY . $timestamp);

        $payload = [
            'BusinessShortCode' => SHORTCODE,
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'TransactionType'   => 'CustomerPayBillOnline',
            'Amount'            => (int) ceil($amount),
            'PartyA'            => $phone,
            'PartyB'            => SHORTCODE,
            'PhoneNumber'       => $phone,
            'CallBackURL'       => CALLBACK_URL,
            'AccountReference'  => 'Order#' . $orderId,
            'TransactionDesc'   => 'Sanaa Ya Kenya Payment',
        ];

        logMpesa('stk_request', json_encode($payload));

        $ch = curl_init(STK_PUSH_URL);
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

        $response  = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        logMpesa('stk_response', "HTTP {$httpCode}: " . ($response ?: $curlError));

        if ($curlError) {
            return ['error' => 'Network error: ' . $curlError];
        }

        $result = json_decode($response, true) ?? [];

        // Save to DB for every attempt (for debugging)
        db()->prepare("
            INSERT INTO mpesa_transactions
                (order_id, phone, amount, merchant_request_id,
                 checkout_request_id, status, result_desc)
            VALUES (?,?,?,?,?,'pending',?)
        ")->execute([
            $orderId, $phone, $amount,
            $result['MerchantRequestID']   ?? '',
            $result['CheckoutRequestID']   ?? '',
            $result['ResponseDescription'] ?? $result['errorMessage'] ?? json_encode($result),
        ]);

        return $result;
    }
}
