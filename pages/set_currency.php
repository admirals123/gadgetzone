<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/currency.php';

$base = base_path();
$code = strtoupper(trim($_REQUEST['code'] ?? 'INR'));

if (isset($GLOBALS['CURRENCIES'][$code])) {
    $_SESSION['active_currency_code'] = $code;
    fetchLiveRates(true); // Force live exchange rate refresh
}

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
       || (isset($_REQUEST['format']) && $_REQUEST['format'] === 'json');

if ($isAjax) {
    header('Content-Type: application/json');
    $active = getActiveCurrency();
    echo json_encode([
        'success' => true,
        'currency' => $active,
        'cart_count' => getCartCount(),
        'formatted_total' => formatPrice(getCartTotal())
    ]);
    exit;
}

$returnUrl = $_REQUEST['return'] ?? ($_SERVER['HTTP_REFERER'] ?? ($base . '/index.php'));
// Prevent open redirect
if (empty($returnUrl) || strpos($returnUrl, 'http') === 0 && parse_url($returnUrl, PHP_URL_HOST) !== ($_SERVER['HTTP_HOST'] ?? 'localhost')) {
    $returnUrl = $base . '/index.php';
}

header('Location: ' . $returnUrl);
exit;
