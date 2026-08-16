<?php
// Comprehensive functional verification test script for GadgetZone
$baseUrl = 'http://localhost/Gadgetzone';
$cookieFile = __DIR__ . '/cookie.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

function httpReq($url, $method = 'GET', $data = [], $cookies = true) {
    global $cookieFile;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    if ($cookies) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    return ['code' => $code, 'body' => $res, 'url' => $effectiveUrl];
}

$passed = 0;
$failed = 0;

function assertTest($name, $condition, $details = '') {
    global $passed, $failed;
    if ($condition) {
        echo " [PASS] $name\n";
        $passed++;
    } else {
        echo "![FAIL] $name: $details\n";
        $failed++;
    }
}

echo "=== GADGETZONE FUNCTIONAL VERIFICATION ===\n\n";

// 1. Home Page
$res = httpReq("$baseUrl/index.php");
assertTest("Home page loads (HTTP 200)", $res['code'] === 200);
assertTest("Hero headline present", strpos($res['body'], 'Your World.') !== false);
assertTest("Categories rendered", strpos($res['body'], 'Smartphones') !== false);
assertTest("Featured products rendered", strpos($res['body'], 'Featured Products') !== false);

// 2. Shop Page with Filters & Sort
$res = httpReq("$baseUrl/pages/shop.php?cat=smartphones&sort=price_asc");
assertTest("Shop page loads with filter", $res['code'] === 200);
assertTest("Filtered by Smartphones", strpos($res['body'], 'iPhone 15 Pro Max') !== false);

// 3. Product Detail Page
$res = httpReq("$baseUrl/pages/product.php?slug=iphone-15-pro-max");
assertTest("Product page loads", $res['code'] === 200);
assertTest("Product title rendered", strpos($res['body'], 'iPhone 15 Pro Max') !== false);
assertTest("Stock info rendered", strpos($res['body'], 'In Stock') !== false);

// 4. AJAX Cart Operations
$res = httpReq("$baseUrl/pages/cart_action.php", "POST", [
    'action' => 'add',
    'product_id' => 1,
    'qty' => 2
]);
$cartData = json_decode($res['body'], true);
assertTest("AJAX Add to Cart", $cartData && $cartData['success'] === true && $cartData['cart_count'] === 2);

// Update Qty
$res = httpReq("$baseUrl/pages/cart_action.php", "POST", [
    'action' => 'update',
    'product_id' => 1,
    'qty' => 3
]);
$cartData = json_decode($res['body'], true);
assertTest("AJAX Update Qty", $cartData && $cartData['cart_count'] === 3);

// 5. Cart Page
$res = httpReq("$baseUrl/pages/cart.php");
assertTest("Cart page loads with item", $res['code'] === 200 && strpos($res['body'], 'iPhone 15 Pro Max') !== false);
assertTest("Free shipping applied on high value cart", strpos($res['body'], 'Free') !== false);

// 6. User Registration
$testEmail = 'testuser_' . time() . '@gadgetzone.com';
$res = httpReq("$baseUrl/pages/register.php", "POST", [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => $testEmail,
    'password' => 'Password123',
    'confirm_password' => 'Password123'
]);
assertTest("User registration redirects to My Account", strpos($res['url'], 'myaccount.php') !== false);

// 7. My Account Dashboard
$res = httpReq("$baseUrl/pages/myaccount.php");
assertTest("My Account displays user greeting", strpos($res['body'], 'Welcome back, John!') !== false);

// 8. Place an Order via Checkout
$res = httpReq("$baseUrl/pages/checkout.php", "POST", [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => $testEmail,
    'phone' => '+8801712345678',
    'address' => '123 Tech Street',
    'city' => 'Dhaka',
    'notes' => 'Deliver between 10am and 4pm',
    'payment_method' => 'cod'
]);
assertTest("Order placement redirects to success page", strpos($res['url'], 'order_success.php') !== false && strpos($res['body'], 'Order Confirmed!') !== false);

// 9. Admin Login
if (file_exists($cookieFile)) unlink($cookieFile);
$res = httpReq("$baseUrl/pages/login.php", "POST", [
    'email' => 'admin@gadgetzone.com',
    'password' => 'Admin@1234'
]);
assertTest("Admin login successful", $res['code'] === 200 && strpos($res['url'], 'admin') !== false);

// 10. Admin Dashboard Overview
$res = httpReq("$baseUrl/admin/index.php");
assertTest("Admin Dashboard loads", $res['code'] === 200 && strpos($res['body'], 'Total Revenue') !== false);
assertTest("Admin KPI cards rendered", strpos($res['body'], 'Total Orders') !== false && strpos($res['body'], 'Total Customers') !== false);

// 11. Admin Products Page & Toggle Featured
$res = httpReq("$baseUrl/admin/products.php");
assertTest("Admin Products catalog loads", $res['code'] === 200 && strpos($res['body'], 'Product Catalog') !== false);

// 12. Admin Orders Page & Status Update
$res = httpReq("$baseUrl/admin/orders.php");
assertTest("Admin Orders list loads", $res['code'] === 200 && strpos($res['body'], 'All Orders') !== false);

// Extract an order ID from the orders table HTML or test with order ID 1
$res = httpReq("$baseUrl/admin/orders.php", "POST", [
    'form' => 'update_order',
    'id' => 1,
    'status' => 'processing',
    'payment_status' => 'paid'
]);
assertTest("Admin updates order status & payment status", strpos($res['body'], 'Order updated successfully') !== false);

// 14. Admin Settings Page (Currency & Stripe Keys)
$res = httpReq("$baseUrl/admin/settings.php", "POST", [
    'form' => 'settings',
    'active_currency' => 'USD',
    'stripe_publishable_key' => 'pk_test_sample12345',
    'stripe_secret_key' => 'sk_test_sample12345',
    'stripe_webhook_secret' => ''
]);
assertTest("Admin saves settings", strpos($res['body'], 'Settings') !== false && strpos($res['body'], 'saved successfully') !== false);

// Switch back to INR default
$res = httpReq("$baseUrl/admin/settings.php", "POST", [
    'form' => 'settings',
    'active_currency' => 'INR',
    'stripe_publishable_key' => 'pk_test_sample12345',
    'stripe_secret_key' => 'sk_test_sample12345',
    'stripe_webhook_secret' => ''
]);

echo "\n=== SUMMARY: $passed Passed, $failed Failed ===\n";
if (file_exists($cookieFile)) unlink($cookieFile);
