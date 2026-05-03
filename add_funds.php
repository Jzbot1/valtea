<?php
require_once 'includes/header.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'] ?? 0;
    $method = $_POST['method'] ?? '';

    if ($amount >= 1) {
        if ($method === 'jzstore') {
            // Redirect to payment gateway
            header("Location: " . BASE_URL . "/includes/JzstoreGateway.php?amount=" . $amount);
            exit;
        }
    } else {
        $message = "Minimum deposit is ₹1.";
        $messageType = "error";
    }
}

$stmt = $db->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY id DESC LIMIT 10");
$stmt->execute([$user['id']]);
$transactions = $stmt->fetchAll();
?>

<div class="max-w-4xl mx-auto">
    <div class="mb-6 text-center md:text-left">
        <h2 class="text-2xl font-black text-white tracking-tight">Add Funds</h2>
        <p class="text-slate-400 text-sm mt-1">Top up your balance instantly</p>
    </div>

    <?php if ($message): ?>
        <div class="p-4 rounded-xl mb-6 flex items-start space-x-3 <?php echo $messageType === 'success' ? 'bg-green-500/10 border-green-500/30 text-green-400' : 'bg-red-500/10 border-red-500/30 text-red-400'; ?> border text-sm">
            <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
            <div><?php echo htmlspecialchars($message); ?></div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Payment Form -->
        <div class="lg:col-span-1">
            <div class="glass-card p-6 shadow-xl sticky top-24">
                <h4 class="text-xs font-bold text-white mb-6 flex items-center uppercase tracking-widest">
                    <i class="fas fa-wallet mr-2 text-cyan-400"></i> New Deposit
                </h4>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 px-1">Method</label>
                        <select name="method" required class="w-full">
                            <option value="jzstore">QR Payment (Instant)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 px-1">Amount (₹)</label>
                        <input type="number" name="amount" min="1" step="0.01" required class="w-full" placeholder="Min. ₹1">
                    </div>
                    <button type="submit" class="w-full btn-primary text-white py-3.5 rounded-xl mt-2 font-black uppercase tracking-widest">
                        Pay Now <i class="fas fa-chevron-right ml-2 text-[8px]"></i>
                    </button>
                </form>
                <div class="mt-6 pt-6 border-t border-white/5 space-y-3">
                    <div class="flex items-center space-x-3 text-slate-400">
                        <i class="fas fa-check-circle text-cyan-400 text-xs"></i>
                        <span class="text-[10px]">Instant delivery</span>
                    </div>
                    <div class="flex items-center space-x-3 text-slate-400">
                        <i class="fas fa-lock text-cyan-400 text-xs"></i>
                        <span class="text-[10px]">Secure payment</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: History -->
        <div class="lg:col-span-2">
            <div class="glass-card overflow-hidden">
                <div class="px-6 py-4 border-b border-white/5 flex justify-between items-center">
                    <h4 class="text-xs font-bold text-white uppercase tracking-widest">Transaction History</h4>
                    <span class="text-[9px] text-slate-500 uppercase font-black">Last 10</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[9px] font-black text-slate-500 uppercase tracking-widest bg-white/5">
                                <th class="px-6 py-3">ID</th>
                                <th class="px-6 py-3">Amount</th>
                                <th class="px-6 py-3">Type</th>
                                <th class="px-6 py-3">Details</th>
                                <th class="px-6 py-3 text-right">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php if (empty($transactions)): ?>
                                <tr><td colspan="5" class="px-6 py-12 text-center text-slate-500 text-xs italic">No transactions found.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($transactions as $tx): ?>
                                <tr class="hover:bg-white/5 transition-colors">
                                    <td class="px-6 py-4 text-[10px] text-slate-500 font-mono">#<?php echo $tx['id']; ?></td>
                                    <td class="px-6 py-4">
                                        <span class="text-xs font-black <?php echo $tx['type'] === 'credit' ? 'text-green-400' : 'text-red-400'; ?>">
                                            <?php echo $tx['type'] === 'credit' ? '+' : '-'; ?>₹<?php echo number_format($tx['amount'], 2); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-0.5 rounded-md text-[8px] font-black uppercase tracking-widest border <?php echo $tx['type'] === 'credit' ? 'bg-green-400/10 text-green-400 border-green-400/20' : 'bg-red-400/10 text-red-400 border-red-400/20'; ?>">
                                            <?php echo $tx['type']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-[10px] text-slate-300 max-w-[150px] truncate">
                                        <?php echo htmlspecialchars($tx['description']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-[9px] text-slate-500 text-right whitespace-nowrap">
                                        <?php echo date('M d, H:i', strtotime($tx['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
