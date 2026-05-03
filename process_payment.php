<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/JzstoreGateway.php';

Auth::checkLogin();
$db = Database::getInstance();

// Ensure schema exists
require_once __DIR__ . '/includes/EkupiGateway.php';
EkupiGateway::ensureSchema($db);

// Fetch current user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: login.php");
    exit;
}

// Fetch Settings
$stmt = $db->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
foreach ($stmt->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

if (!isset($_GET['amount']) || (float)$_GET['amount'] < 1) {
    header("Location: add_funds.php");
    exit;
}

$amount = (float)$_GET['amount'];
$clientTxnId = 'TXN' . time() . rand(100, 999);

// Get Settings
$gatewaySettings = JzstoreGateway::getSettings($db);

// Prepare Payload
$payload = [
    'customer_mobile' => $user['mobile'] ?? '9876543210',
    'customer_name' => $user['name'] ?? 'Customer',
    'customer_email' => $user['email'] ?? 'customer@example.com',
    'amount' => $amount,
    'client_txn_id' => $clientTxnId,
    'redirect_url' => $gatewaySettings['redirect_url'],
    'p_info' => 'Wallet Top-up',
    'user_id' => $user['id']
];

$active_gateway = $settings['active_gateway'] ?? 'jzstore';
$error = null;

if ($active_gateway === 'jzstore') {
    $gatewaySettings = JzstoreGateway::getSettings($db);
    $response = JzstoreGateway::createOrder($gatewaySettings, $payload);
    
    if ($response['ok']) {
        $redirect_url = $response['data']['result']['payment_url'] ?? null;
        if ($redirect_url) {
            $stmt = $db->prepare("INSERT INTO payment_orders (user_id, amount, client_txn_id, p_info, status, redirect_url) VALUES (?, ?, ?, ?, 'created', ?)");
            $stmt->execute([$user['id'], $amount, $clientTxnId, 'Wallet Top-up via JZStore', $gatewaySettings['redirect_url']]);
            header("Location: " . $redirect_url);
            exit;
        } else {
            $error = "Payment URL not received from JZStore.";
        }
    } else {
        $error = $response['error'] ?? 'JZStore initiation failed.';
    }
} else {
    // eKupi Gateway
    require_once __DIR__ . '/includes/EkupiGateway.php';
    $gatewaySettings = EkupiGateway::getSettings($db);
    $response = EkupiGateway::createOrder($gatewaySettings, $payload);
    
    if ($response['ok']) {
        $redirect_url = $response['data']['data']['payment_url'] ?? null;
        if ($redirect_url) {
            $stmt = $db->prepare("INSERT INTO payment_orders (user_id, amount, client_txn_id, p_info, status, redirect_url) VALUES (?, ?, ?, ?, 'created', ?)");
            $stmt->execute([$user['id'], $amount, $clientTxnId, 'Wallet Top-up via eKupi', $gatewaySettings['redirect_url']]);
            header("Location: " . $redirect_url);
            exit;
        } else {
            $error = "Payment URL not received from eKupi.";
        }
    } else {
        $error = $response['error'] ?? 'eKupi initiation failed.';
    }
}

// Only if there is an error do we continue to show the UI
require_once 'includes/header.php';
?>

<div class="max-w-md mx-auto py-12">
    <div class="glass-card p-8 text-center">
        <div class="w-16 h-16 bg-red-500/10 text-red-500 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-exclamation-triangle text-2xl"></i>
        </div>
        <h2 class="text-xl font-bold text-white mb-2">Payment Error</h2>
        <p class="text-slate-400 text-sm mb-6"><?php echo htmlspecialchars($error); ?></p>
        <a href="add_funds.php" class="btn-primary text-white py-3 px-8 rounded-xl inline-block font-bold uppercase tracking-widest text-xs">
            Try Again
        </a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
