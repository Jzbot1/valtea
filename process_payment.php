<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/JzstoreGateway.php';

Auth::checkLogin();
$db = Database::getInstance();

// Fetch current user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: login.php");
    exit;
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
    'amount' => $amount,
    'client_txn_id' => $clientTxnId,
    'redirect_url' => $gatewaySettings['redirect_url'],
    'p_info' => 'Wallet Top-up',
    'user_id' => $user['id']
];

// Create Order
$response = JzstoreGateway::createOrder($gatewaySettings, $payload);

$error = null;
if ($response['ok']) {
    $redirect_url = $response['data']['result']['payment_url'] ?? null;
    if ($redirect_url) {
        // Record initiated transaction in payment_orders
        $stmt = $db->prepare("INSERT INTO payment_orders (user_id, amount, client_txn_id, p_info, status, redirect_url) VALUES (?, ?, ?, ?, 'created', ?)");
        $stmt->execute([$user['id'], $amount, $clientTxnId, 'Wallet Top-up via QR', $gatewaySettings['redirect_url']]);
        
        header("Location: " . $redirect_url);
        exit;
    } else {
        $error = "Payment URL not received from gateway.";
    }
} else {
    $error = $response['error'] ?? 'Failed to initiate payment.';
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
