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
    <div class="mb-6 text-center md:text-left">
        <h2 class="text-2xl font-black text-white tracking-tight">Account Settings</h2>
        <p class="text-slate-400 text-sm mt-1">Manage your security and API access</p>
    </div>

    <?php if ($message): ?>
        <div class="p-4 rounded-xl mb-6 flex items-start space-x-3 <?php echo $messageType === 'success' ? 'bg-green-500/10 border-green-500/30 text-green-400' : 'bg-red-500/10 border-red-500/30 text-red-400'; ?> border text-sm">
            <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
            <div><?php echo htmlspecialchars($message); ?></div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Stats & Info -->
        <div class="lg:col-span-1 space-y-6">
            <div class="glass-card p-6 text-center">
                <div class="w-16 h-16 bg-cyan-400/10 text-cyan-400 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-cyan-400/20">
                    <i class="fas fa-user-circle text-3xl"></i>
                </div>
                <h3 class="text-lg font-bold text-white"><?php echo htmlspecialchars($user['name']); ?></h3>
                <p class="text-slate-500 text-[11px] mt-1"><?php echo htmlspecialchars($user['email']); ?></p>
                <div class="mt-4 pt-4 border-t border-white/5">
                    <p class="text-[9px] text-slate-500 uppercase font-black tracking-widest mb-0.5">Total Spent</p>
                    <p class="text-xl font-black text-white">₹<?php echo number_format($stats['Spent'], 2); ?></p>
                </div>
            </div>

            <div class="glass-card p-6">
                <h4 class="text-xs font-bold text-white mb-4 flex items-center uppercase tracking-widest">
                    <i class="fas fa-chart-pie mr-2 text-cyan-400"></i> Stats
                </h4>
                <div class="space-y-3">
                    <?php 
                    $stat_items = [
                        ['Total', 'Total Orders', 'text-slate-400'],
                        ['Completed', 'Completed', 'text-green-400'],
                        ['Processing', 'Processing', 'text-cyan-400'],
                        ['Pending', 'Pending', 'text-amber-400'],
                        ['Canceled', 'Canceled', 'text-red-400'],
                    ];
                    foreach ($stat_items as $item): 
                        $val = $item[0] === 'Processing' ? ($stats['Processing'] + $stats['In progress']) : $stats[$item[0]];
                    ?>
                        <div class="flex justify-between items-center text-[11px]">
                            <span class="<?php echo $item[2]; ?> font-medium uppercase tracking-tight"><?php echo $item[1]; ?></span>
                            <span class="text-white font-black"><?php echo $val; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Right Column: Security & API -->
        <div class="lg:col-span-2 space-y-6">
            <!-- API Settings -->
            <div class="glass-card p-6">
                <h4 class="text-lg font-bold text-white mb-4 flex items-center">
                    <i class="fas fa-key mr-3 text-cyan-400"></i> API Access
                </h4>
                <div class="bg-slate-950/40 border border-white/5 rounded-xl p-4 mb-4">
                    <label class="block text-[9px] text-slate-500 uppercase font-black tracking-widest mb-1.5">API Key</label>
                    <div class="flex items-center space-x-3">
                        <input type="text" id="api_key" readonly value="<?php echo htmlspecialchars($user['api_key']); ?>" class="flex-1 bg-transparent !border-none text-cyan-400 font-mono text-xs focus:ring-0 !p-0">
                        <button onclick="copyApi()" class="text-slate-500 hover:text-white transition-colors">
                            <i class="fas fa-copy text-sm"></i>
                        </button>
                    </div>
                </div>
                <form method="POST" onsubmit="return confirm('Regenerating will invalidate your old key. Continue?');">
                    <input type="hidden" name="action" value="regen_api">
                    <button type="submit" class="text-[10px] font-black text-cyan-400 hover:text-white flex items-center transition-colors uppercase tracking-widest">
                        <i class="fas fa-sync-alt mr-2"></i> REGENERATE KEY
                    </button>
                </form>
            </div>

            <!-- Password Change -->
            <div class="glass-card p-6">
                <h4 class="text-lg font-bold text-white mb-4 flex items-center">
                    <i class="fas fa-shield-alt mr-3 text-cyan-400"></i> Security
                </h4>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="change_password">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 px-1">Current Password</label>
                        <input type="password" name="current_password" required class="w-full">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 px-1">New Password</label>
                            <input type="password" name="new_password" required class="w-full">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 px-1">Confirm New</label>
                            <input type="password" name="confirm_password" required class="w-full">
                        </div>
                    </div>
                    <button type="submit" class="btn-primary text-white py-3 px-8 rounded-xl mt-2">
                        Update Security
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
