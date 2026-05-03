<?php
ob_start();
require_once 'includes/header.php';
require_once 'includes/EkupiGateway.php';
require_once 'includes/JzstoreGateway.php';

$message = '';
EkupiGateway::ensureSchema($db);
$all_settings = [];
$stmt = $db->query("SELECT setting_key, setting_value FROM settings");
foreach ($stmt->fetchAll() as $row) {
    $all_settings[$row['setting_key']] = $row['setting_value'];
}

$active_gateway = $all_settings['active_gateway'] ?? 'ekupi';

if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
        $message = 'Payment successful. Wallet credited.';
    } elseif ($_GET['status'] === 'failed') {
        $message = 'Payment failed or pending. You can retry or check later.';
    }
}

// Auto-check pending payments for this user
$stmt = $db->prepare("SELECT client_txn_id FROM payment_orders WHERE user_id = ? AND status = 'created' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
$stmt->execute([$user['id']]);
$pendings = $stmt->fetchAll();

foreach ($pendings as $p) {
    if ($active_gateway === 'jzstore') {
        $gw_settings = JzstoreGateway::getSettings($db);
        $check = JzstoreGateway::checkOrderStatus($gw_settings, $p['client_txn_id']);
        
        $rawJzStatus = strtolower((string)($check['data']['status'] ?? $check['data']['data']['status'] ?? ''));
        $isOk = in_array($rawJzStatus, ['success', 'completed', '1', 'true']);
        if (!$isOk && isset($check['data']['status']) && $check['data']['status'] === true) {
            $innerStatus = strtolower((string)($check['data']['data']['status'] ?? ''));
            $isOk = in_array($innerStatus, ['success', 'completed']);
            if (!$isOk && !isset($check['data']['data']['status'])) {
                $isOk = true;
            }
        }
    } else {
        $gw_settings = EkupiGateway::getSettings($db);
        $check = EkupiGateway::checkOrderStatus($gw_settings, $p['client_txn_id']);
        $rawStatus = strtolower((string)($check['data']['data']['status'] ?? ''));
        $isOk = in_array($rawStatus, ['success', 'completed', 'success_scan', 'scan_pay']);
    }

    if ($isOk) {
        $utr = (string)($check['data']['data']['utr'] ?? '');
        EkupiGateway::finalizeSuccess($db, $p['client_txn_id'], $utr, json_encode($check['data']));
        $message = 'Payment confirmed! Your balance has been updated.';
        // Refresh user data to show new balance
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $user = $stmt->fetch();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)($_POST['amount'] ?? 0);
    $mobile = preg_replace('/\D+/', '', (string)($_POST['mobile'] ?? ''));

    if ($amount < 5) {
        $message = 'Minimum amount is INR 5';
    } elseif (strlen($mobile) < 10) {
        $message = 'Please enter a valid mobile number.';
    } else {
        $clientTxnId = 'FUND' . $user['id'] . '_' . time() . '_' . random_int(1000, 9999);
        $redirectUrl = ($active_gateway === 'jzstore') 
            ? ($all_settings['jzstore_redirect_url'] ?? '') 
            : ($all_settings['ekupi_redirect_url'] ?? '');

        if ($redirectUrl === '') {
            $message = 'Gateway is not properly configured (missing redirect URL).';
        } else {
            $payload = [
                'user_id' => $user['id'],
                'client_txn_id' => $clientTxnId,
                'amount' => number_format($amount, 2, '.', ''),
                'p_info' => 'Wallet Add Fund',
                'customer_name' => $user['name'],
                'customer_email' => $user['email'],
                'customer_mobile' => $mobile,
                'redirect_url' => $redirectUrl,
                'udf1' => 'uid:' . $user['id'],
                'udf2' => '',
                'udf3' => ''
            ];

            try {
                $stmt = $db->prepare("
                    INSERT INTO payment_orders
                    (user_id, client_txn_id, amount, status, p_info, customer_name, customer_email, customer_mobile, redirect_url, udf1, udf2, udf3)
                    VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $user['id'], $clientTxnId, $amount, $payload['p_info'],
                    $payload['customer_name'], $payload['customer_email'], $payload['customer_mobile'],
                    $payload['redirect_url'], $payload['udf1'], $payload['udf2'], $payload['udf3']
                ]);

                if ($active_gateway === 'jzstore') {
                    $gw_settings = JzstoreGateway::getSettings($db);
                    $res = JzstoreGateway::createOrder($gw_settings, $payload);
                } else {
                    $gw_settings = EkupiGateway::getSettings($db);
                    $res = EkupiGateway::createOrder($gw_settings, $payload);
                }

                if (!$res['ok']) {
                    EkupiGateway::markFailed($db, $clientTxnId, json_encode($res));
                    $message = 'Gateway Error: ' . $res['error'];
                } else {
                    $data = ($active_gateway === 'jzstore') ? ($res['data']['result'] ?? []) : ($res['data']['data'] ?? []);
                    $paymentUrl = $data['payment_url'] ?? '';
                    $orderId = ($active_gateway === 'jzstore') ? ($data['orderId'] ?? null) : ($data['order_id'] ?? null);

                    if ($paymentUrl === '') {
                        EkupiGateway::markFailed($db, $clientTxnId, json_encode($res['data']));
                        $message = 'Gateway returned invalid payment URL.';
                    } else {
                        $stmt = $db->prepare("UPDATE payment_orders SET status = 'created', gateway_order_id = ?, gateway_response = ? WHERE client_txn_id = ?");
                        $stmt->execute([$orderId, json_encode($res['data']), $clientTxnId]);
                        header('Location: ' . $paymentUrl);
                        exit;
                    }
                }
            } catch (Throwable $e) {
                $message = 'Initialization failed: ' . $e->getMessage();
            }
        }
    }
}

$stmt = $db->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY id DESC LIMIT 50");
$stmt->execute([$user['id']]);
$transactions = $stmt->fetchAll();
?>

<div class="max-w-5xl mx-auto space-y-10 mb-20">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-4xl font-black text-white tracking-tight">Add Funds</h2>
            <p class="text-slate-400 mt-1">Instant wallet credit via secure payment gateways.</p>
        </div>
        <div class="flex items-center space-x-4 bg-slate-900/50 p-4 rounded-2xl border border-slate-700/50">
            <div class="w-10 h-10 bg-green-500/10 text-green-500 rounded-xl flex items-center justify-center">
                <i class="fas fa-wallet"></i>
            </div>
            <div>
                <p class="text-[10px] text-slate-500 uppercase font-black tracking-widest">Current Balance</p>
                <p class="text-xl font-black text-white">₹<?php echo number_format($user['balance'], 2); ?></p>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="p-4 rounded-2xl flex items-start space-x-3 <?php echo strpos($message, 'successful') !== false ? 'bg-green-500/10 border-green-500/20 text-green-400' : 'bg-red-500/10 border-red-500/20 text-red-400'; ?> border animate-in fade-in slide-in-from-top-4 duration-300">
            <i class="fas <?php echo strpos($message, 'successful') !== false ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
            <div class="text-sm font-medium"><?php echo htmlspecialchars($message); ?></div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        <!-- Deposit Form -->
        <div class="lg:col-span-1">
            <div class="glass-card rounded-3xl p-8 border border-white/5 shadow-2xl relative overflow-hidden">
                <div class="absolute -top-24 -right-24 w-48 h-48 bg-indigo-600/10 rounded-full blur-3xl"></div>
                
                <h3 class="text-xl font-bold text-white mb-8 flex items-center">
                    <span class="w-8 h-8 bg-indigo-600/20 text-indigo-400 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-plus text-xs"></i>
                    </span>
                    Deposit Money
                </h3>

                <form method="POST" action="" class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Amount (INR)</label>
                        <div class="relative">
                            <span class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-500 font-bold">₹</span>
                            <input type="number" step="0.01" min="5" name="amount" required class="w-full bg-slate-950/50 border border-slate-700/50 rounded-2xl pl-10 pr-5 py-4 text-white focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all font-bold text-lg" placeholder="100.00">
                        </div>
                        <p class="text-[10px] text-slate-500 mt-2 px-1 font-medium">Minimum deposit is ₹5.00</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Mobile Number</label>
                        <input type="text" name="mobile" minlength="10" maxlength="15" required class="w-full bg-slate-950/50 border border-slate-700/50 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all" placeholder="Enter 10-digit number">
                    </div>

                    <div class="p-4 rounded-2xl bg-indigo-500/5 border border-indigo-500/10 flex items-center space-x-3">
                        <div class="bg-indigo-600/20 text-indigo-400 w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-shield-alt text-xs"></i>
                        </div>
                        <p class="text-[10px] text-slate-400 leading-tight">Your payment is secured by industry-standard encryption.</p>
                    </div>

                    <button type="submit" class="w-full btn-primary text-white font-black py-5 rounded-2xl shadow-xl shadow-indigo-600/20 active:scale-[0.98] transition-all flex items-center justify-center space-x-3">
                        <span>PAY SECURELY</span>
                        <i class="fas fa-arrow-right text-xs"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- Transaction History -->
        <div class="lg:col-span-2">
            <div class="glass-card rounded-3xl p-8 border border-white/5 shadow-2xl">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-xl font-bold text-white flex items-center">
                        <span class="w-8 h-8 bg-indigo-600/20 text-indigo-400 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-history text-xs"></i>
                        </span>
                        Recent Transactions
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-slate-500 text-[10px] uppercase tracking-widest border-b border-slate-700/50">
                                <th class="pb-4 px-4 font-bold">Details</th>
                                <th class="pb-4 px-4 font-bold">Date</th>
                                <th class="pb-4 px-4 font-bold text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php foreach ($transactions as $tx): ?>
                                <tr class="border-b border-slate-700/30 hover:bg-slate-800/20 transition-colors group">
                                    <td class="py-4 px-4">
                                        <div class="text-white font-bold"><?php echo htmlspecialchars($tx['description']); ?></div>
                                        <div class="text-slate-500 text-[10px] font-mono mt-0.5">ID: #<?php echo $tx['id']; ?></div>
                                    </td>
                                    <td class="py-4 px-4 text-slate-400 text-xs">
                                        <?php echo date('M d, Y', strtotime($tx['created_at'])); ?>
                                    </td>
                                    <td class="py-4 px-4 text-right">
                                        <span class="font-black <?php echo $tx['type'] === 'credit' ? 'text-green-400' : 'text-red-400'; ?>">
                                            <?php echo $tx['type'] === 'credit' ? '+' : '-'; ?>₹<?php echo number_format($tx['amount'], 2); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="3" class="py-20 text-center">
                                        <div class="w-16 h-16 bg-slate-800/50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-700/30">
                                            <i class="fas fa-receipt text-slate-600"></i>
                                        </div>
                                        <p class="text-slate-500 text-sm font-medium">No transactions yet.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
