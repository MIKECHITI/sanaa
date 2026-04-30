<?php
// ============================================================
// mpesa/config.php — Daraja API constants
// Single source of truth for all M-Pesa files.
// ============================================================

require_once __DIR__ . '/../includes/config.php';

// Bridge from main config to mpesa constants
defined('CONSUMER_KEY')    || define('CONSUMER_KEY',    MPESA_CONSUMER_KEY);
defined('CONSUMER_SECRET') || define('CONSUMER_SECRET', MPESA_CONSUMER_SECRET);
defined('SHORTCODE')       || define('SHORTCODE',       MPESA_SHORTCODE);
defined('PASSKEY')         || define('PASSKEY',         MPESA_PASSKEY);
defined('CALLBACK_URL')    || define('CALLBACK_URL',    MPESA_CALLBACK_URL);

// Safaricom base URL — sandbox vs live
$_base = (MPESA_ENV === 'production' || MPESA_ENV === 'live')
    ? 'https://api.safaricom.co.ke'
    : 'https://sandbox.safaricom.co.ke';

defined('MPESA_BASE_URL') || define('MPESA_BASE_URL', $_base);
defined('TOKEN_URL')      || define('TOKEN_URL',      MPESA_BASE_URL . '/oauth/v1/generate?grant_type=client_credentials');
defined('STK_PUSH_URL')   || define('STK_PUSH_URL',   MPESA_BASE_URL . '/mpesa/stkpush/v1/processrequest');
defined('QUERY_URL')      || define('QUERY_URL',       MPESA_BASE_URL . '/mpesa/stkpushquery/v1/query');

// Log directory
defined('LOG_DIR') || define('LOG_DIR', __DIR__ . '/logs/');
