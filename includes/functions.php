<?php
/**
 * Core helper functions: auth, cart, formatting, sanitation.
 * Requires db.php (for $conn) to already be included.
 */

// -----------------------------------------------------------------
// PATH HELPERS
// -----------------------------------------------------------------
if (!function_exists('base_path')) {
    function base_path() {
        // Auto-detect localhost /Gadgetzone or /gadget subfolder vs VPS root, mirrors main.js logic
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#^(/[^/]+)#', $script, $m) && stripos($m[1], 'gadget') !== false) {
            return $m[1];
        }
        if (preg_match('#^(/[^/]+)#', $uri, $m) && stripos($m[1], 'gadget') !== false) {
            return $m[1];
        }
        return '';
    }
}

// -----------------------------------------------------------------
// AUTH
// -----------------------------------------------------------------
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . base_path() . '/pages/login.php');
        exit;
    }
}

function isAdmin() {
    return isLoggedIn() && in_array($_SESSION['role'] ?? '', ['admin', 'super_admin'], true);
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ' . base_path() . '/pages/login.php');
        exit;
    }
}

function getCurrentUser() {
    global $conn;
    if (!isLoggedIn()) return null;
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $user ?: null;
}

// -----------------------------------------------------------------
// SANITIZATION
// -----------------------------------------------------------------
function sanitize($data) {
    global $conn;
    $data = trim($data ?? '');
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// -----------------------------------------------------------------
// CART  ($_SESSION['cart'] = [product_id => quantity])
// -----------------------------------------------------------------
function getCart() {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return $_SESSION['cart'];
}

function addToCart($productId, $qty = 1) {
    $productId = (int)$productId;
    $qty = max(1, (int)$qty);
    $cart = getCart();
    if (isset($cart[$productId])) {
        $cart[$productId] += $qty;
    } else {
        $cart[$productId] = $qty;
    }
    $cart[$productId] = min(99, $cart[$productId]);
    $_SESSION['cart'] = $cart;
    return true;
}

function updateCartQty($productId, $qty) {
    $productId = (int)$productId;
    $qty = (int)$qty;
    $cart = getCart();
    if ($qty <= 0) {
        unset($cart[$productId]);
    } else {
        $cart[$productId] = min(99, $qty);
    }
    $_SESSION['cart'] = $cart;
    return true;
}

function removeFromCart($productId) {
    $productId = (int)$productId;
    $cart = getCart();
    unset($cart[$productId]);
    $_SESSION['cart'] = $cart;
    return true;
}

function getCartCount() {
    $cart = getCart();
    return array_sum($cart);
}

function getCartItemsWithDetails() {
    global $conn;
    $cart = getCart();
    if (empty($cart)) return [];

    $ids = array_map('intval', array_keys($cart));
    $idsList = implode(',', $ids);
    $result = mysqli_query($conn, "SELECT p.*, c.name AS category_name
                                    FROM products p
                                    LEFT JOIN categories c ON c.id = p.category_id
                                    WHERE p.id IN ($idsList)");
    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['qty'] = $cart[$row['id']];
        $row['line_subtotal'] = $row['price'] * $row['qty'];
        $items[] = $row;
    }
    return $items;
}

function getCartTotal() {
    $items = getCartItemsWithDetails();
    $total = 0;
    foreach ($items as $item) {
        $total += $item['line_subtotal'];
    }
    return $total;
}

// -----------------------------------------------------------------
// MISC
// -----------------------------------------------------------------
function generateOrderNumber() {
    return 'GZ-' . strtoupper(uniqid());
}

function timeAgo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    return floor($diff / 86400) . 'd ago';
}

function getSetting($key, $default = '') {
    global $conn;
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        $result = mysqli_query($conn, "SELECT setting_key, setting_value FROM settings");
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $cache[$row['setting_key']] = $row['setting_value'];
            }
        }
    }
    return $cache[$key] ?? $default;
}
