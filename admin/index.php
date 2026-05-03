<?php
require_once 'includes/header.php';

require_once __DIR__ . '/../includes/SmmApi.php';

$stats = [];

$stmt = $db->query("SELECT COUNT(*) FROM users");
$stats['users'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM orders");
$stats['orders'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT SUM(amount) FROM transactions WHERE type = 'credit'");
$stats['revenue'] = $stmt->fetchColumn() ?: 0;

$stmt = $db->query("SELECT SUM(charge - api_charge) FROM orders WHERE status = 'Completed'");
$stats['profit'] = $stmt->fetchColumn() ?: 0;

// Fetch SMM Provider Balance
$api = new SmmApi();
$balance_res = $api->balance();
$provider_balance = isset($balance_res->balance) ? $balance_res->balance : '0.00';
$provider_currency = isset($balance_res->currency) ? $balance_res->currency : 'INR';

if (isset($balance_res->error)) {
    $provider_balance = 'Error';
    $provider_currency = '';
}
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-white">Dashboard Overview</h2>
    <div class="glass px-4 py-2 rounded-xl flex items-center border border-indigo-500/30">
        <div class="mr-3">
            <p class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Provider Balance</p>
            <p class="text-lg font-bold text-indigo-400"><?php echo $provider_currency . ' ' . number_format((float)$provider_balance, 2); ?></p>
        </div>
        <div class="w-10 h-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400">
            <i class="fas fa-wallet"></i>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="glass p-6 rounded-2xl shadow-lg border-t-4 border-indigo-500">
        <h3 class="text-slate-400 text-sm font-medium mb-2">Total Users</h3>
        <p class="text-3xl font-bold text-white"><?php echo number_format($stats['users']); ?></p>
    </div>
    <div class="glass p-6 rounded-2xl shadow-lg border-t-4 border-blue-500">
        <h3 class="text-slate-400 text-sm font-medium mb-2">Total Orders</h3>
        <p class="text-3xl font-bold text-white"><?php echo number_format($stats['orders']); ?></p>
    </div>
    <div class="glass p-6 rounded-2xl shadow-lg border-t-4 border-green-500">
        <h3 class="text-slate-400 text-sm font-medium mb-2">Revenue (Added Funds)</h3>
        <p class="text-3xl font-bold text-white">₹<?php echo number_format($stats['revenue'], 2); ?></p>
    </div>
    <div class="glass p-6 rounded-2xl shadow-lg border-t-4 border-yellow-500">
        <h3 class="text-slate-400 text-sm font-medium mb-2">Est. Net Profit</h3>
        <p class="text-3xl font-bold text-white">₹<?php echo number_format($stats['profit'], 2); ?></p>
    </div>
</div>

<!-- Cron Status Section -->
<div class="glass border border-slate-700/50 rounded-3xl p-8 shadow-2xl mb-8 overflow-hidden relative">
    <!-- Decorative Pulse -->
    <div class="absolute top-8 right-8 flex items-center space-x-2">
        <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">System Health</span>
        <?php
        $last_run = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'last_cron_status_run'")->fetchColumn();
        $is_active = $last_run && (time() - strtotime($last_run) < 600); // Active if run in last 10 mins
        ?>
        <div class="w-3 h-3 rounded-full <?php echo $is_active ? 'bg-green-500 shadow-[0_0_12px_rgba(34,197,94,0.6)]' : 'bg-red-500 shadow-[0_0_12px_rgba(239,68,68,0.6)]'; ?> animate-pulse"></div>
    </div>

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h3 class="text-xl font-bold text-white flex items-center">
                <span class="w-10 h-10 bg-indigo-600/20 text-indigo-400 rounded-xl flex items-center justify-center mr-4">
                    <i class="fas fa-clock"></i>
                </span>
                Order Status Cron
            </h3>
            <p class="text-slate-400 text-sm mt-1">
                Last Run: <span class="text-indigo-400 font-mono"><?php echo $last_run ? date('M d, H:i:s', strtotime($last_run)) : 'Never'; ?></span>
            </p>
        </div>
        
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center space-x-2 bg-slate-950/50 border border-slate-700/50 rounded-xl px-4 py-2">
                <code class="text-[10px] text-slate-400 font-mono"><?php echo (FULL_URL ?? '') . '/cron/status'; ?></code>
                <button onclick="copyToClipboard('<?php echo (FULL_URL ?? '') . '/cron/status'; ?>')" class="text-indigo-400 hover:text-white transition-colors">
                    <i class="fas fa-copy text-xs"></i>
                </button>
            </div>
            <a href="<?php echo BASE_URL; ?>/cron/status" target="_blank" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-3 px-6 rounded-xl transition-all shadow-lg shadow-indigo-600/20 active:scale-95 flex items-center">
                <i class="fas fa-sync-alt mr-2"></i> MANUAL SYNC
            </a>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Cron URL copied to clipboard!');
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
