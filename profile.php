<?php
require_once 'includes/header.php';

$message = '';
$messageType = '';

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (password_verify($current_pass, $user['password'])) {
        if ($new_pass === $confirm_pass) {
            if (strlen($new_pass) >= 6) {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($stmt->execute([$hashed, $user['id']])) {
                    $message = "Password updated successfully!";
                    $messageType = "success";
                } else {
                    $message = "Failed to update password.";
                    $messageType = "error";
                }
            } else {
                $message = "New password must be at least 6 characters long.";
                $messageType = "error";
            }
        } else {
            $message = "New passwords do not match.";
            $messageType = "error";
        }
    } else {
        $message = "Current password is incorrect.";
        $messageType = "error";
    }
}

// Handle API Key Regeneration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'regen_api') {
    $new_api_key = Auth::generateApiKey();
    $stmt = $db->prepare("UPDATE users SET api_key = ? WHERE id = ?");
    if ($stmt->execute([$new_api_key, $user['id']])) {
        $user['api_key'] = $new_api_key;
        $message = "API Key regenerated successfully!";
        $messageType = "success";
    } else {
        $message = "Failed to regenerate API Key.";
        $messageType = "error";
    }
}

// Fetch Order Stats
$stats = [];
$statuses = ['Completed', 'Pending', 'In progress', 'Processing', 'Partial', 'Canceled', 'Refunded'];
foreach ($statuses as $status) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = ?");
    $stmt->execute([$user['id'], $status]);
    $stats[$status] = $stmt->fetchColumn();
}

$stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
$stmt->execute([$user['id']]);
$stats['Total'] = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT SUM(charge) FROM orders WHERE user_id = ?");
$stmt->execute([$user['id']]);
$stats['Spent'] = $stmt->fetchColumn() ?: 0;
?>

<div class="max-w-4xl mx-auto">
    <div class="mb-8">
        <h2 class="text-3xl font-black text-white">Account Settings</h2>
        <p class="text-slate-400 mt-1">Manage your profile, security, and API access.</p>
    </div>

    <?php if ($message): ?>
        <div class="p-4 rounded-2xl mb-8 flex items-start space-x-3 <?php echo $messageType === 'success' ? 'bg-green-500/10 border-green-500/30 text-green-400' : 'bg-red-500/10 border-red-500/30 text-red-400'; ?> border animate-in fade-in slide-in-from-top-4 duration-300">
            <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
            <div class="text-sm font-medium"><?php echo htmlspecialchars($message); ?></div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column: Stats & Info -->
        <div class="lg:col-span-1 space-y-6">
            <div class="glass-card rounded-3xl p-6 shadow-xl text-center">
                <div class="w-20 h-20 bg-indigo-600/20 text-indigo-400 rounded-3xl flex items-center justify-center mx-auto mb-4 border border-indigo-500/20">
                    <i class="fas fa-user-circle text-4xl"></i>
                </div>
                <h3 class="text-xl font-bold text-white"><?php echo htmlspecialchars($user['name']); ?></h3>
                <p class="text-slate-400 text-xs mt-1"><?php echo htmlspecialchars($user['email']); ?></p>
                <div class="mt-6 pt-6 border-t border-slate-700/50">
                    <p class="text-[10px] text-slate-500 uppercase font-black tracking-widest mb-1">Total Spent</p>
                    <p class="text-2xl font-black text-white">₹<?php echo number_format($stats['Spent'], 2); ?></p>
                </div>
            </div>

            <div class="glass-card rounded-3xl p-6 shadow-xl">
                <h4 class="text-sm font-bold text-white mb-4 flex items-center">
                    <i class="fas fa-chart-pie mr-2 text-indigo-400"></i> Order Statistics
                </h4>
                <div class="space-y-3">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-400">Total Orders</span>
                        <span class="text-white font-bold"><?php echo $stats['Total']; ?></span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-green-400">Completed</span>
                        <span class="text-white font-bold"><?php echo $stats['Completed']; ?></span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-yellow-400">Processing</span>
                        <span class="text-white font-bold"><?php echo $stats['Processing'] + $stats['In progress']; ?></span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-blue-400">Pending</span>
                        <span class="text-white font-bold"><?php echo $stats['Pending']; ?></span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-red-400">Canceled</span>
                        <span class="text-white font-bold"><?php echo $stats['Canceled']; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Security & API -->
        <div class="lg:col-span-2 space-y-6">
            <!-- API Settings -->
            <div class="glass-card rounded-3xl p-8 shadow-xl">
                <h4 class="text-xl font-bold text-white mb-6 flex items-center">
                    <i class="fas fa-key mr-3 text-indigo-400"></i> API Access
                </h4>
                <div class="bg-slate-950/50 border border-slate-700/50 rounded-2xl p-4 mb-6">
                    <label class="block text-[10px] text-slate-500 uppercase font-black tracking-widest mb-2 px-1">Your API Key</label>
                    <div class="flex items-center space-x-3">
                        <input type="text" id="api_key" readonly value="<?php echo htmlspecialchars($user['api_key']); ?>" class="flex-1 bg-transparent border-none text-indigo-400 font-mono text-sm focus:ring-0">
                        <button onclick="copyApi()" class="text-slate-400 hover:text-white transition-colors">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                <form method="POST" onsubmit="return confirm('Regenerating will invalidate your old key. Continue?');">
                    <input type="hidden" name="action" value="regen_api">
                    <button type="submit" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 flex items-center transition-colors">
                        <i class="fas fa-sync-alt mr-2"></i> REGENERATE KEY
                    </button>
                </form>
            </div>

            <!-- Password Change -->
            <div class="glass-card rounded-3xl p-8 shadow-xl">
                <h4 class="text-xl font-bold text-white mb-6 flex items-center">
                    <i class="fas fa-shield-alt mr-3 text-indigo-400"></i> Change Password
                </h4>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="change_password">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Current Password</label>
                            <input type="password" name="current_password" required class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl px-5 py-3 text-white focus:outline-none focus:border-indigo-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">New Password</label>
                            <input type="password" name="new_password" required class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl px-5 py-3 text-white focus:outline-none focus:border-indigo-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Confirm New Password</label>
                            <input type="password" name="confirm_password" required class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl px-5 py-3 text-white focus:outline-none focus:border-indigo-500 transition-all">
                        </div>
                    </div>
                    <button type="submit" class="btn-primary text-white font-bold py-4 px-8 rounded-2xl shadow-xl mt-4">
                        Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function copyApi() {
    const copyText = document.getElementById("api_key");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value);
    alert("API Key copied to clipboard!");
}
</script>

<?php require_once 'includes/footer.php'; ?>
