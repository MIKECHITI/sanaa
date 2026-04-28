<?php
// ============================================================
// api/add-to-cart.php  — AJAX endpoint
// ============================================================
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

verifyCsrf();

$pid = cleanInt($_POST['product_id'] ?? 0);
$qty = max(1, cleanInt($_POST['qty'] ?? 1));

if (!$pid) {
    echo json_encode(['success' => false, 'error' => 'Invalid product']);
    exit;
}

$st = db()->prepare("SELECT id, title, price, stock FROM products WHERE id = ? AND status = 'approved'");
$st->execute([$pid]);
$product = $st->fetch();

if (!$product) {
    echo json_encode(['success' => false, 'error' => 'Product not found']);
    exit;
}

// Add / update cart
$current = $_SESSION['cart'][$pid]['qty'] ?? 0;
$newQty  = min($product['stock'], $current + $qty);

$_SESSION['cart'][$pid] = [
    'id'    => $product['id'],
    'title' => $product['title'],
    'price' => $product['price'],
    'qty'   => $newQty,
];

$totalItems = array_sum(array_column($_SESSION['cart'], 'qty'));

echo json_encode([
    'success'    => true,
    'cart_count' => $totalItems,
    'message'    => '"' . $product['title'] . '" added to cart.',
]);
