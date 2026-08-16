<?php
/**
 * Multi-currency support.
 * All prices are stored in BDT in the database and converted on the fly.
 */

$GLOBALS['CURRENCIES'] = [
    'INR' => ['symbol' => '₹',    'name' => 'Indian Rupee',     'rate' => 1.00,   'stripe' => 'inr'],
    'USD' => ['symbol' => '$',    'name' => 'US Dollar',        'rate' => 0.0120, 'stripe' => 'usd'],
    'EUR' => ['symbol' => '€',    'name' => 'Euro',             'rate' => 0.0110, 'stripe' => 'eur'],
    'GBP' => ['symbol' => '£',    'name' => 'British Pound',    'rate' => 0.0095, 'stripe' => 'gbp'],
    'CAD' => ['symbol' => 'C$',   'name' => 'Canadian Dollar',  'rate' => 0.0163, 'stripe' => 'cad'],
    'AUD' => ['symbol' => 'A$',   'name' => 'Australian Dollar','rate' => 0.0184, 'stripe' => 'aud'],
    'BDT' => ['symbol' => '৳',    'name' => 'Bangladeshi Taka', 'rate' => 1.3200, 'stripe' => 'bdt'],
    'SGD' => ['symbol' => 'S$',   'name' => 'Singapore Dollar', 'rate' => 0.0161, 'stripe' => 'sgd'],
    'SAR' => ['symbol' => 'ريال', 'name' => 'Saudi Riyal',      'rate' => 0.0450, 'stripe' => 'sar'],
    'AED' => ['symbol' => 'د.إ',  'name' => 'UAE Dirham',       'rate' => 0.0440, 'stripe' => 'aed'],
    'JPY' => ['symbol' => '¥',    'name' => 'Japanese Yen',     'rate' => 1.8300, 'stripe' => 'jpy'],
    'MYR' => ['symbol' => 'RM',   'name' => 'Malaysian Ringgit','rate' => 0.0550, 'stripe' => 'myr'],
];

/**
 * Fetches live market exchange rates with INR as base.
 * Caches in session / static memory for 30 minutes to ensure fast page loads
 * while keeping rates fresh and live.
 */
function fetchLiveRates($force = false) {
    static $liveRatesLoaded = false;
    if ($liveRatesLoaded && !$force) return;

    $cacheTime = $_SESSION['live_rates_time'] ?? 0;
    $now = time();

    // Refresh every 30 minutes or when forced
    if (!$force && isset($_SESSION['live_rates']) && is_array($_SESSION['live_rates']) && ($now - $cacheTime < 1800)) {
        foreach ($_SESSION['live_rates'] as $code => $rate) {
            if (isset($GLOBALS['CURRENCIES'][$code])) {
                $GLOBALS['CURRENCIES'][$code]['rate'] = (float)$rate;
            }
        }
        $liveRatesLoaded = true;
        return;
    }

    // Try fetching live rates from live exchange rates API
    $endpoints = [
        'https://open.er-api.com/v6/latest/INR',
        'https://api.exchangerate-api.com/v4/latest/INR'
    ];

    $fetchedRates = [];
    foreach ($endpoints as $url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) GadgetZone/1.0');
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            if (!empty($data['rates']) && is_array($data['rates'])) {
                $fetchedRates = $data['rates'];
                break;
            }
        }
    }

    if (!empty($fetchedRates)) {
        $cached = ['INR' => 1.0];
        foreach ($GLOBALS['CURRENCIES'] as $code => &$info) {
            if ($code === 'INR') {
                $info['rate'] = 1.0;
            } elseif (isset($fetchedRates[$code])) {
                $info['rate'] = (float)$fetchedRates[$code];
                $cached[$code] = (float)$fetchedRates[$code];
            }
        }
        unset($info);
        $_SESSION['live_rates'] = $cached;
        $_SESSION['live_rates_time'] = $now;
    }

    $liveRatesLoaded = true;
}

function getEnabledCurrencies($force = false) {
    global $conn, $_ENABLED_CURRENCIES_CACHE;
    if (!$force && $_ENABLED_CURRENCIES_CACHE !== null) {
        return $_ENABLED_CURRENCIES_CACHE;
    }

    $enabledCodes = [];
    if (isset($conn) && $conn) {
        $result = mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key = 'enabled_currencies' LIMIT 1");
        if ($result && $row = mysqli_fetch_assoc($result)) {
            $decoded = json_decode($row['setting_value'], true);
            if (is_array($decoded) && !empty($decoded)) {
                $enabledCodes = $decoded;
            } elseif (!empty($row['setting_value'])) {
                $enabledCodes = array_map('trim', explode(',', $row['setting_value']));
            }
        }
    }

    if (empty($enabledCodes)) {
        $enabledCodes = array_keys($GLOBALS['CURRENCIES']);
    }

    $enabled = [];
    foreach ($enabledCodes as $c) {
        $c = strtoupper($c);
        if (isset($GLOBALS['CURRENCIES'][$c])) {
            $enabled[$c] = $GLOBALS['CURRENCIES'][$c];
        }
    }

    if (empty($enabled)) {
        $enabled['INR'] = $GLOBALS['CURRENCIES']['INR'];
    }

    $_ENABLED_CURRENCIES_CACHE = $enabled;
    return $enabled;
}

function getActiveCurrency() {
    global $conn;
    fetchLiveRates();
    $enabled = getEnabledCurrencies();

    $code = 'INR';
    if (isset($_SESSION['active_currency_code']) && isset($enabled[$_SESSION['active_currency_code']])) {
        $code = $_SESSION['active_currency_code'];
    } else {
        if (isset($conn) && $conn) {
            $result = mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key = 'active_currency' LIMIT 1");
            if ($result && $row = mysqli_fetch_assoc($result)) {
                if (isset($enabled[$row['setting_value']])) {
                    $code = $row['setting_value'];
                }
            }
        }
        if (!isset($enabled[$code])) {
            $code = array_key_first($enabled);
        }
        $_SESSION['active_currency_code'] = $code;
    }
    return array_merge(['code' => $code], $GLOBALS['CURRENCIES'][$code]);
}

function invalidateCurrencyCache() {
    global $_ENABLED_CURRENCIES_CACHE;
    $_ENABLED_CURRENCIES_CACHE = null;
    unset($_SESSION['active_currency_code']);
    unset($_SESSION['live_rates']);
    unset($_SESSION['live_rates_time']);
}

function convertAmount($bdt) {
    $currency = getActiveCurrency();
    return $bdt * $currency['rate'];
}

function formatPrice($bdt) {
    $currency = getActiveCurrency();
    $amount = convertAmount($bdt);
    // Zero-decimal currencies or whole amounts for INR/BDT
    $decimals = 2;
    if (in_array($currency['code'], ['INR', 'BDT', 'JPY'], true) || $amount == floor($amount)) {
        $decimals = ($currency['code'] === 'JPY') ? 0 : (($amount == floor($amount)) ? 0 : 2);
    }
    return $currency['symbol'] . ' ' . number_format($amount, $decimals);
}

function getStripeAmount($bdt) {
    $currency = getActiveCurrency();
    $amount = convertAmount($bdt);
    // Zero-decimal currencies for Stripe (e.g. JPY) are not multiplied by 100
    $zeroDecimal = ['jpy'];
    if (in_array($currency['stripe'], $zeroDecimal, true)) {
        return (int) round($amount);
    }
    return (int) round($amount * 100);
}

function getStripeCurrencyCode() {
    return getActiveCurrency()['stripe'];
}
