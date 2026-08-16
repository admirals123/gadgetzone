<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/currency.php';
requireAdmin();

$pageTitle = 'Settings';
$base = base_path();
$message = '';
$error = '';

function upsertSetting($conn, $key, $value) {
    $stmt = mysqli_prepare($conn, "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    mysqli_stmt_bind_param($stmt, "ss", $key, $value);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'settings') {
    $enabledPosted = $_POST['enabled_currencies'] ?? [];
    $validEnabled = [];
    foreach ($enabledPosted as $c) {
        $c = strtoupper(trim($c));
        if (isset($GLOBALS['CURRENCIES'][$c])) {
            $validEnabled[] = $c;
        }
    }

    $activeCurrency = strtoupper(trim($_POST['active_currency'] ?? 'INR'));
    if (!isset($GLOBALS['CURRENCIES'][$activeCurrency])) {
        $activeCurrency = 'INR';
    }

    // Ensure default active currency is always in the enabled list
    if (!in_array($activeCurrency, $validEnabled, true)) {
        $validEnabled[] = $activeCurrency;
    }

    if (empty($validEnabled)) {
        $validEnabled = ['INR'];
        $activeCurrency = 'INR';
    }

    $pubKey = trim($_POST['stripe_publishable_key'] ?? '');
    $secKey = trim($_POST['stripe_secret_key'] ?? '');
    $webhookSecret = trim($_POST['stripe_webhook_secret'] ?? '');

    upsertSetting($conn, 'enabled_currencies', json_encode(array_values($validEnabled)));
    upsertSetting($conn, 'active_currency', $activeCurrency);
    upsertSetting($conn, 'stripe_publishable_key', $pubKey);
    upsertSetting($conn, 'stripe_secret_key', $secKey);
    upsertSetting($conn, 'stripe_webhook_secret', $webhookSecret);

    invalidateCurrencyCache();
    $message = 'Settings & storefront currency options saved successfully.';
}

// Reload fresh settings
$settingsRows = mysqli_fetch_all(mysqli_query($conn, "SELECT setting_key, setting_value FROM settings"), MYSQLI_ASSOC);
$settings = [];
foreach ($settingsRows as $row) $settings[$row['setting_key']] = $row['setting_value'];

$activeCurrency = $settings['active_currency'] ?? 'INR';
$enabledCurrenciesRaw = $settings['enabled_currencies'] ?? '';
$enabledCurrencies = [];
if (!empty($enabledCurrenciesRaw)) {
    $decoded = json_decode($enabledCurrenciesRaw, true);
    if (is_array($decoded)) {
        $enabledCurrencies = $decoded;
    }
}
if (empty($enabledCurrencies)) {
    $enabledCurrencies = array_keys($GLOBALS['CURRENCIES']);
}

$stripeConfigured = !empty($settings['stripe_publishable_key']) && strpos($settings['stripe_publishable_key'], 'REPLACE') === false;

// Ensure live rates are fetched for display
fetchLiveRates();

require_once __DIR__ . '/layout.php';
?>

<?php if ($message): ?><div class="alert alert-success">✅ <?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger">⚠️ <?= e($error) ?></div><?php endif; ?>

<form method="POST">
  <input type="hidden" name="form" value="settings">

  <div class="admin-panel">
    <div class="admin-panel-head" style="margin-bottom:8px;">
      <div>
        <h3 style="margin-bottom:4px;">💱 Storefront Currency Control</h3>
        <p style="color:var(--text2); font-size:13px; margin:0;">
          Select which currencies are displayed in the storefront top navigation dropdown. Pick one as the <strong>Default Currency</strong>.
        </p>
      </div>
      <div style="display:flex; gap:8px;">
        <button type="button" class="btn-outline btn-sm" onclick="selectAllCurrencies(true)">Select All</button>
        <button type="button" class="btn-outline btn-sm" onclick="selectMajorOnly()">Major Only (INR, USD, EUR, GBP)</button>
      </div>
    </div>

    <div class="currency-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th style="width:70px; text-align:center;">Enable</th>
            <th>Currency</th>
            <th>Code</th>
            <th>Symbol</th>
            <th>Live Market Rate (Base INR)</th>
            <th style="text-align:center;">Default Currency</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($GLOBALS['CURRENCIES'] as $code => $c): 
            $isEnabled = in_array($code, $enabledCurrencies, true);
            $isDefault = ($code === $activeCurrency);
          ?>
            <tr class="currency-row <?= $isEnabled ? '' : 'disabled' ?>" id="row_<?= $code ?>">
              <td style="text-align:center;">
                <input type="checkbox" name="enabled_currencies[]" value="<?= e($code) ?>" id="chk_<?= $code ?>"
                       class="cur-chk" <?= $isEnabled ? 'checked' : '' ?> onchange="toggleCurRow('<?= $code ?>')">
              </td>
              <td>
                <label for="chk_<?= $code ?>" style="cursor:pointer; font-weight:600; color:var(--text);">
                  <?= e($c['name']) ?>
                </label>
              </td>
              <td><strong style="color:var(--accent); font-family:var(--font-head);"><?= e($code) ?></strong></td>
              <td style="font-size:16px;"><?= e($c['symbol']) ?></td>
              <td style="color:var(--text2); font-family:var(--font-head);">
                <?= $code === 'INR' ? '1.00 <span style="color:var(--accent); font-size:11px;">(Base)</span>' : number_format($c['rate'], 6) ?>
              </td>
              <td style="text-align:center;">
                <label style="cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                  <input type="radio" name="active_currency" value="<?= e($code) ?>" <?= $isDefault ? 'checked' : '' ?>
                         onchange="ensureCurEnabled('<?= $code ?>')">
                  <span style="font-size:12px; color:var(--text2);">Set Default</span>
                </label>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <p style="color:var(--text2); font-size:12px; margin-top:8px;">
      💡 Prices are dynamically converted using real-time Google/market exchange rates. Only the enabled currencies will be selectable by visitors in the top header switcher.
    </p>
  </div>

  <div class="admin-panel">
    <div class="admin-panel-head" style="margin-bottom:16px;">
      <h3 style="margin-bottom:0;">💳 Stripe Configuration</h3>
      <span class="pay-status <?= $stripeConfigured ? 'pay-paid' : 'pay-unpaid' ?>"><?= $stripeConfigured ? '✅ Connected' : '⚠️ Not Configured' ?></span>
    </div>
    <div class="form-group"><label>Publishable Key (pk_test_...)</label><input type="text" name="stripe_publishable_key" value="<?= e($settings['stripe_publishable_key'] ?? '') ?>"></div>
    <div class="form-group"><label>Secret Key (sk_test_...)</label><input type="text" name="stripe_secret_key" value="<?= e($settings['stripe_secret_key'] ?? '') ?>"></div>
    <div class="form-group"><label>Webhook Secret (optional)</label><input type="text" name="stripe_webhook_secret" value="<?= e($settings['stripe_webhook_secret'] ?? '') ?>"></div>
    <p style="color:var(--text2); font-size:13px;">Get your test keys from <a href="https://dashboard.stripe.com" target="_blank" style="color:var(--accent);">dashboard.stripe.com</a> (enable Test Mode → Developers → API keys).</p>
  </div>

  <button type="submit" class="btn-primary btn-lg">💾 Save All Settings</button>
</form>

<script>
function toggleCurRow(code) {
  const chk = document.getElementById('chk_' + code);
  const row = document.getElementById('row_' + code);
  if (chk && row) {
    if (chk.checked) {
      row.classList.remove('disabled');
    } else {
      row.classList.add('disabled');
      // If unchecked row was radio selected default, don't allow unchecking or re-check
      const radio = document.querySelector('input[name="active_currency"]:checked');
      if (radio && radio.value === code) {
        chk.checked = true;
        row.classList.remove('disabled');
        alert('The default active currency (' + code + ') must remain enabled.');
      }
    }
  }
}

function ensureCurEnabled(code) {
  const chk = document.getElementById('chk_' + code);
  const row = document.getElementById('row_' + code);
  if (chk) {
    chk.checked = true;
    if (row) row.classList.remove('disabled');
  }
}

function selectAllCurrencies(check) {
  document.querySelectorAll('.cur-chk').forEach(c => {
    c.checked = check;
    toggleCurRow(c.value);
  });
}

function selectMajorOnly() {
  const major = ['INR', 'USD', 'EUR', 'GBP'];
  document.querySelectorAll('.cur-chk').forEach(c => {
    c.checked = major.includes(c.value);
    toggleCurRow(c.value);
  });
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
