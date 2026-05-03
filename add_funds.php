<?php
ob_start();
require_once(__DIR__ . '/config/config.php'); 
require_once(__DIR__ . '/includes/header.php');
require_once 'includes/EkupiGateway.php';
require_once 'includes/JzstoreGateway.php';

$message = '';
$messageType = 'success';
EkupiGateway::ensureSchema($db);

$all_settings = [];
$stmt = $db->query("SELECT setting_key, setting_value FROM settings");
foreach ($stmt->fetchAll() as $row) {
    $all_settings[$row['setting_key']] = $row['setting_value'];
}

$active_gateway = $all_settings['active_gateway'] ?? 'jzstore';

if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
        $message = 'Payment successful. Wallet credited.';
        $messageType = 'success';
    } elseif ($_GET['status'] === 'failed') {
        $message = 'Payment failed or pending. You can retry or check later.';
        $messageType = 'error';
    }
}

if (isset($_GET['error'])) {
    $message = htmlspecialchars($_GET['error']);
    $messageType = 'error';
}

// Auto-check pending payments for this user (Self-Healing)
$stmt = $db->prepare("SELECT client_txn_id FROM payment_orders WHERE user_id = ? AND status = 'created' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
$stmt->execute([$user['id']]);
$pendings = $stmt->fetchAll();

foreach ($pendings as $p) {
    $isOk = false;
    $check = null;
    $utr = '';

    if ($active_gateway === 'jzstore') {
        $gw_settings = JzstoreGateway::getSettings($db);
        $check = JzstoreGateway::checkOrderStatus($gw_settings, $p['client_txn_id']);
        
        if ($check['ok']) {
            $apiStatus = $check['data']['status'] ?? false;
            $orderResult = $check['data']['result'] ?? [];
            $orderStatus = strtolower((string)($orderResult['status'] ?? ''));
            if ($apiStatus === true && in_array($orderStatus, ['success', 'completed', 'success_scan', 'scan_pay'])) {
                $isOk = true;
            }
            $utr = (string)($orderResult['utr'] ?? '');
        }
    } else {
        $gw_settings = EkupiGateway::getSettings($db);
        $check = EkupiGateway::checkOrderStatus($gw_settings, $p['client_txn_id']);
        if ($check['ok']) {
            $rawStatus = strtolower((string)($check['data']['data']['status'] ?? ''));
            $isOk = in_array($rawStatus, ['success', 'completed', 'success_scan', 'scan_pay']);
            $utr = (string)($check['data']['data']['utr'] ?? '');
        }
    }

    if ($isOk) {
        if (EkupiGateway::finalizeSuccess($db, $p['client_txn_id'], $utr, json_encode($check['data']))) {
            $message = 'Payment confirmed! Your balance has been updated.';
            $messageType = 'success';
            // Refresh user data to show new balance
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $user = $stmt->fetch();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)($_POST['amount'] ?? 0);
    $mobile = preg_replace('/\D+/', '', (string)($_POST['mobile'] ?? ''));

    if ($amount < 1) {
        $message = 'Minimum amount is ₹1.00';
        $messageType = 'error';
    } elseif (strlen($mobile) < 10) {
        $message = 'Please enter a valid 10-digit mobile number.';
        $messageType = 'error';
    } else {
        $clientTxnId = 'TXN' . $user['id'] . '_' . time() . '_' . random_int(100, 999);
        
        // Use full URL for redirects
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $base_url = $protocol . "://" . $host . BASE_URL;
        $finalCallbackUrl = $base_url . "/payment_callback.php?client_txn_id=" . $clientTxnId;

        $payload = [
            'user_id' => $user['id'],
            'client_txn_id' => $clientTxnId,
            'amount' => number_format($amount, 2, '.', ''),
            'p_info' => 'Wallet Top-up',
            'customer_name' => $user['name'],
            'customer_email' => $user['email'],
            'customer_mobile' => $mobile,
            'redirect_url' => $finalCallbackUrl
        ];

        try {
            // First record the attempt
            $stmt = $db->prepare("
                INSERT INTO payment_orders
                (user_id, client_txn_id, amount, status, p_info, customer_name, customer_email, customer_mobile, redirect_url)
                VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user['id'], $clientTxnId, $amount, $payload['p_info'],
                $payload['customer_name'], $payload['customer_email'], $payload['customer_mobile'],
                $payload['redirect_url']
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
                $messageType = 'error';
            } else {
                $data = ($active_gateway === 'jzstore') ? ($res['data']['result'] ?? []) : ($res['data']['data'] ?? []);
                $paymentUrl = $data['payment_url'] ?? '';
                $orderId = ($active_gateway === 'jzstore') ? ($data['orderId'] ?? null) : ($data['order_id'] ?? null);

                if ($paymentUrl === '') {
                    EkupiGateway::markFailed($db, $clientTxnId, json_encode($res['data']));
                    $message = 'Gateway returned invalid payment URL.';
                    $messageType = 'error';
                } else {
                    $stmt = $db->prepare("UPDATE payment_orders SET status = 'created', gateway_order_id = ?, gateway_response = ? WHERE client_txn_id = ?");
                    $stmt->execute([$orderId, json_encode($res['data']), $clientTxnId]);
                    header('Location: ' . $paymentUrl);
                    exit;
                }
            }
        } catch (Throwable $e) {
            $message = 'Initialization failed: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

$stmt = $db->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY id DESC LIMIT 20");
$stmt->execute([$user['id']]);
$transactions = $stmt->fetchAll();
?>

<style>
    .glass-card {
        background: rgba(15, 23, 42, 0.4) !important;
        backdrop-filter: blur(24px) !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
    }
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
        height: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.02);
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.2);
    }
</style>

<div class="max-w-5xl mx-auto space-y-10 mb-20 pt-10">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-4xl font-black text-white tracking-tight">Add Funds</h2>
            <p class="text-slate-400 mt-1 italic">Instant wallet credit via secure QR payment.</p>
        </div>
        <div class="flex items-center space-x-4 bg-slate-950/40 p-5 rounded-3xl border border-white/5 backdrop-blur-xl">
            <div class="w-12 h-12 bg-indigo-500/10 text-indigo-400 rounded-2xl flex items-center justify-center border border-indigo-500/20 shadow-lg shadow-indigo-500/5">
                <i class="fas fa-wallet text-xl"></i>
            </div>
            <div>
                <p class="text-[10px] text-slate-500 uppercase font-black tracking-widest">My Balance</p>
                <p class="text-2xl font-black text-white">₹<?php echo number_format($user['balance'], 2); ?></p>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="p-5 rounded-2xl flex items-start space-x-4 <?php echo $messageType === 'success' ? 'bg-green-500/10 border-green-500/20 text-green-400' : 'bg-red-500/10 border-red-500/20 text-red-400'; ?> border animate-in fade-in slide-in-from-top-4 duration-500 shadow-2xl">
            <div class="mt-0.5">
                <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> text-lg"></i>
            </div>
            <div class="text-sm font-semibold tracking-wide"><?php echo htmlspecialchars($message); ?></div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        <!-- Deposit Form -->
        <div class="lg:col-span-1">
            <div class="glass-card rounded-[2.5rem] p-10 border border-white/10 shadow-2xl relative overflow-hidden">
                <div class="absolute -top-24 -right-24 w-48 h-48 bg-cyan-400/10 rounded-full blur-[80px]"></div>
                
                <h3 class="text-xl font-black text-white mb-8 flex items-center uppercase tracking-tight">
                    <span class="w-10 h-10 bg-indigo-600/20 text-indigo-400 rounded-xl flex items-center justify-center mr-4 border border-indigo-600/30">
                        <i class="fas fa-plus text-sm"></i>
                    </span>
                    Deposit
                </h3>

                <form method="POST" action="" class="space-y-8">
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 px-1">Gateway</label>
                        <div class="p-4 bg-slate-950/60 rounded-2xl border border-white/5 flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-200"><?php echo $active_gateway === 'jzstore' ? 'JZStore QR' : 'eKupi'; ?></span>
                            <i class="fas fa-check-circle text-indigo-400 text-xs"></i>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 px-1">Amount (INR)</label>
                        <div class="relative">
                            <span class="absolute left-6 top-1/2 -translate-y-1/2 text-slate-400 font-black text-xl">₹</span>
                            <input type="number" step="0.01" min="1" name="amount" required class="w-full bg-slate-950/60 border border-white/10 rounded-2xl pl-12 pr-6 py-5 text-white focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all font-black text-2xl placeholder-slate-800" placeholder="0.00">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 px-1">Mobile Number</label>
                        <div class="relative">
                            <span class="absolute left-6 top-1/2 -translate-y-1/2 text-slate-400"><i class="fas fa-phone-alt"></i></span>
                            <input type="text" name="mobile" minlength="10" maxlength="15" required class="w-full bg-slate-950/60 border border-white/10 rounded-2xl pl-14 pr-6 py-4 text-white focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all font-bold" placeholder="10-digit number">
                        </div>
                    </div>

                    <div class="p-5 rounded-2xl bg-indigo-500/5 border border-indigo-500/10 flex items-center space-x-4">
                        <div class="bg-indigo-600/20 text-indigo-400 w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 border border-indigo-600/20">
                            <i class="fas fa-shield-halved text-sm"></i>
                        </div>
                        <p class="text-[9px] text-slate-400 font-medium leading-relaxed uppercase tracking-wider">Secure 256-bit SSL Encrypted Transaction</p>
                    </div>

                    <button type="submit" class="w-full btn-primary text-white font-black py-6 rounded-2xl shadow-2xl shadow-indigo-600/30 active:scale-[0.97] transition-all flex items-center justify-center space-x-3 group">
                        <span class="tracking-widest uppercase">PROCEED TO PAY</span>
                        <i class="fas fa-chevron-right text-[10px] group-hover:translate-x-1 transition-transform"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- Transaction History -->
        <div class="lg:col-span-2">
            <div class="glass-card rounded-[2.5rem] p-10 border border-white/5 shadow-2xl backdrop-blur-2xl">
                <div class="flex items-center justify-between mb-10">
                    <h3 class="text-xl font-black text-white flex items-center uppercase tracking-tight">
                        <span class="w-10 h-10 bg-indigo-600/20 text-indigo-400 rounded-xl flex items-center justify-center mr-4 border border-indigo-600/30">
                            <i class="fas fa-history text-sm"></i>
                        </span>
                        History
                    </h3>
                </div>

                <div class="overflow-x-auto -mx-10 px-10 custom-scrollbar">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-slate-500 text-[9px] font-black uppercase tracking-[0.2em] border-b border-white/5">
                                <th class="pb-6 px-4">Description</th>
                                <th class="pb-6 px-4">Date</th>
                                <th class="pb-6 px-4 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php foreach ($transactions as $tx): ?>
                                <tr class="border-b border-white/5 hover:bg-white/5 transition-all group">
                                    <td class="py-6 px-4">
                                        <div class="text-white font-bold tracking-wide"><?php echo htmlspecialchars($tx['description']); ?></div>
                                        <div class="text-slate-600 text-[10px] font-mono mt-1 flex items-center">
                                            <i class="fas fa-hashtag mr-1 text-[8px]"></i> <?php echo $tx['id']; ?>
                                        </div>
                                    </td>
                                    <td class="py-6 px-4">
                                        <div class="text-slate-400 font-bold text-xs uppercase tracking-tighter">
                                            <?php echo date('M d, Y', strtotime($tx['created_at'])); ?>
                                        </div>
                                        <div class="text-[9px] text-slate-600 font-medium mt-1">
                                            <?php echo date('h:i A', strtotime($tx['created_at'])); ?>
                                        </div>
                                    </td>
                                    <td class="py-6 px-4 text-right">
                                        <span class="font-black text-base tracking-tighter <?php echo $tx['type'] === 'credit' ? 'text-green-400' : 'text-red-400'; ?>">
                                            <?php echo $tx['type'] === 'credit' ? '+' : '-'; ?>₹<?php echo number_format($tx['amount'], 2); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="3" class="py-24 text-center">
                                        <div class="w-20 h-20 bg-slate-900/50 rounded-full flex items-center justify-center mx-auto mb-6 border border-white/5 shadow-inner">
                                            <i class="fas fa-receipt text-slate-700 text-2xl"></i>
                                        </div>
                                        <p class="text-slate-500 text-sm font-bold uppercase tracking-widest">No transactions yet</p>
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
