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

<div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div class="text-center md:text-left">
        <h2 class="text-2xl font-black text-white tracking-tight">Admin Dashboard</h2>
        <p class="text-slate-400 text-sm mt-1">Manage your SMM empire at a glance</p>
    </div>
    <div class="glass px-5 py-3 rounded-2xl flex items-center border border-white/10">
        <div class="mr-4 text-right">
            <p class="text-[9px] text-slate-500 uppercase font-black tracking-widest">Provider Balance</p>
            <p class="text-lg font-black text-cyan-400"><?php echo $provider_currency . ' ' . number_format((float)$provider_balance, 2); ?></p>
        </div>
        <div class="w-10 h-10 rounded-xl bg-cyan-400/10 flex items-center justify-center text-cyan-400 border border-cyan-400/20">
            <i class="fas fa-wallet"></i>
        </div>
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8">
    <div class="glass-card p-5 relative overflow-hidden group">
        <div class="absolute -top-10 -right-10 w-24 h-24 bg-cyan-400/5 rounded-full blur-2xl group-hover:bg-cyan-400/10 transition-colors"></div>
        <h3 class="text-slate-500 text-[10px] font-black uppercase tracking-widest mb-1">Users</h3>
        <p class="text-2xl font-black text-white"><?php echo number_format($stats['users']); ?></p>
        <div class="mt-4 flex items-center text-green-400 text-[10px] font-bold">
            <i class="fas fa-arrow-up mr-1"></i> Active
        </div>
    </div>
    <div class="glass-card p-5 relative overflow-hidden group">
        <div class="absolute -top-10 -right-10 w-24 h-24 bg-blue-400/5 rounded-full blur-2xl group-hover:bg-blue-400/10 transition-colors"></div>
        <h3 class="text-slate-500 text-[10px] font-black uppercase tracking-widest mb-1">Orders</h3>
        <p class="text-2xl font-black text-white"><?php echo number_format($stats['orders']); ?></p>
        <div class="mt-4 flex items-center text-blue-400 text-[10px] font-bold">
            <i class="fas fa-shopping-basket mr-1"></i> History
        </div>
    </div>
    <div class="glass-card p-5 relative overflow-hidden group">
        <div class="absolute -top-10 -right-10 w-24 h-24 bg-green-400/5 rounded-full blur-2xl group-hover:bg-green-400/10 transition-colors"></div>
        <h3 class="text-slate-500 text-[10px] font-black uppercase tracking-widest mb-1">Revenue</h3>
        <p class="text-2xl font-black text-white">₹<?php echo number_format($stats['revenue'], 2); ?></p>
        <div class="mt-4 flex items-center text-green-400 text-[10px] font-bold">
            <i class="fas fa-chart-line mr-1"></i> Total
        </div>
    </div>
    <div class="glass-card p-5 relative overflow-hidden group">
        <div class="absolute -top-10 -right-10 w-24 h-24 bg-amber-400/5 rounded-full blur-2xl group-hover:bg-amber-400/10 transition-colors"></div>
        <h3 class="text-slate-500 text-[10px] font-black uppercase tracking-widest mb-1">Net Profit</h3>
        <p class="text-2xl font-black text-white">₹<?php echo number_format($stats['profit'], 2); ?></p>
        <div class="mt-4 flex items-center text-amber-400 text-[10px] font-bold">
            <i class="fas fa-coins mr-1"></i> Estimated
        </div>
    </div>
</div>

<!-- System Health Section -->
<div class="glass-card p-6 md:p-8 mb-8 relative overflow-hidden">
    <div class="absolute top-6 right-6 flex items-center space-x-2">
        <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Cron Status</span>
        <?php
        $last_run = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'last_cron_status_run'")->fetchColumn();
        $is_active = $last_run && (time() - strtotime($last_run) < 600);
        ?>
        <div class="w-2.5 h-2.5 rounded-full <?php echo $is_active ? 'bg-green-500 shadow-[0_0_12px_rgba(34,197,94,0.6)]' : 'bg-red-500 shadow-[0_0_12px_rgba(239,68,68,0.6)]'; ?> animate-pulse"></div>
    </div>

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h3 class="text-lg font-black text-white flex items-center">
                <div class="w-10 h-10 bg-white/5 text-cyan-400 rounded-xl flex items-center justify-center mr-4 border border-white/5">
                    <i class="fas fa-clock"></i>
                </div>
                Order Sync Worker
            </h3>
            <p class="text-slate-400 text-[11px] mt-1 uppercase tracking-tight">
                Last Heartbeat: <span class="text-cyan-400 font-bold"><?php echo $last_run ? date('M d, H:i:s', strtotime($last_run)) : 'Offline'; ?></span>
            </p>
        </div>
        
        <div class="flex flex-col md:flex-row items-center gap-3">
            <div class="flex items-center space-x-3 bg-slate-950/40 border border-white/5 rounded-xl px-4 py-2.5">
                <code class="text-[10px] text-slate-500 font-mono"><?php echo (FULL_URL ?? '') . '/cron/status'; ?></code>
                <button onclick="copyToClipboard('<?php echo (FULL_URL ?? '') . '/cron/status'; ?>')" class="text-cyan-400 hover:text-white transition-colors">
                    <i class="fas fa-copy text-xs"></i>
                </button>
            </div>
            <a href="<?php echo BASE_URL; ?>/cron/status" target="_blank" class="btn-primary text-white text-[10px] font-black py-3 px-6 rounded-xl uppercase tracking-widest shadow-xl shadow-cyan-400/10 active:scale-95">
                <i class="fas fa-sync-alt mr-2"></i> Trigger Sync
            </a>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Cron URL copied successfully!');
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
