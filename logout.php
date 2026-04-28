<?php
// logout.php
require_once __DIR__ . '/includes/auth.php';
Auth::logout();
redirect(APP_URL . '/pages/home.php');
