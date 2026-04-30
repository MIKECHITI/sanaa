<?php
// ============================================================
// mpesa/token.php — Gets OAuth token from Safaricom
// ============================================================
require_once __DIR__ . '/config.php';

function getMpesaToken(): string
{
    // Return cached token if still valid
    if (
        isset($_SESSION['mpesa_token'], $_SESSION['mpesa_token_expires']) &&
        $_SESSION['mpesa_token_expires'] > time()
    ) {
        return $_SESSION['mpesa_token'];
    }

    $credentials = base64_encode(CONSUMER_KEY . ':' . CONSUMER_SECRET);

    $ch = curl_init(TOKEN_URL);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => ['Authorization: Basic ' . $credentials],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,  // false for sandbox (avoids SSL errors on localhost)
        CURLOPT_TIMEOUT        => 30,
    ]);

    $response  = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    logMpesa('token_response', $response ?: ('CURL ERROR: ' . $curlError));

    if ($curlError) {
        throw new RuntimeException('Cannot reach Safaricom: ' . $curlError);
    }

    $data = json_decode($response, true);

    if (empty($data['access_token'])) {
        throw new RuntimeException('Token error — Safaricom said: ' . $response);
    }

    // Cache for 55 minutes
    $_SESSION['mpesa_token']         = $data['access_token'];
    $_SESSION['mpesa_token_expires'] = time() + 3300;

    return $data['access_token'];
}

// Write a timestamped line to the daily log file
function logMpesa(string $label, string $data): void
{
    if (!is_dir(LOG_DIR)) mkdir(LOG_DIR, 0755, true);
    $line = date('Y-m-d H:i:s') . " | {$label} | {$data}" . PHP_EOL;
    file_put_contents(LOG_DIR . 'mpesa_' . date('Y-m-d') . '.log', $line, FILE_APPEND);
}
