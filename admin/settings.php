<?php
require_once 'includes/header.php';
require_once __DIR__ . '/../includes/Telegram.php';

$message = '';

function upsertSetting(PDO $db, string $key, string $value): void {
    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute([$key, $value]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to_save = [
        'site_name', 'site_url', 'currency', 'min_add_fund',
        'facebook_url', 'twitter_url', 'instagram_url', 'telegram_url',
        'telegram_bot_token', 'telegram_chat_id',
        'active_gateway', 'ekupi_key', 'ekupi_base_url', 'ekupi_redirect_url', 'ekupi_webhook_token',
        'jzstore_token', 'jzstore_base_url', 'jzstore_redirect_url',
        'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_from_email', 'smtp_from_name',
        'maintenance_mode',
        'google_auth_enabled', 'google_client_id', 'google_client_secret',
        'announcement_enabled', 'announcement_text'
    ];
    
    foreach ($to_save as $key) {
        if (isset($_POST[$key])) {
            upsertSetting($db, $key, trim((string)$_POST[$key]));
        }
    }

    $profit_percent = $_POST['profit_percent'] ?? 5;
    $smm_api_url = $_POST['smm_api_url'] ?? '';
    $smm_api_key = $_POST['smm_api_key'] ?? '';
    $support_number = $_POST['support_number'] ?? '';

    upsertSetting($db, 'profit_percent', (string)$profit_percent);
    upsertSetting($db, 'smm_api_url', (string)$smm_api_url);
    upsertSetting($db, 'smm_api_key', (string)$smm_api_key);
    upsertSetting($db, 'support_number', trim((string)$support_number));

    // Handle Logo Upload
    if (!empty($_FILES['site_logo']['name'])) {
        $ext = pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION);
        $logo_name = 'logo_' . time() . '.' . $ext;
        $target = __DIR__ . '/../assets/images/' . $logo_name;
        if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $target)) {
            upsertSetting($db, 'site_logo', BASE_URL . '/assets/images/' . $logo_name);
        }
    }

    // Handle Favicon Upload
    if (!empty($_FILES['site_favicon']['name'])) {
        $ext = pathinfo($_FILES['site_favicon']['name'], PATHINFO_EXTENSION);
        $fav_name = 'favicon_' . time() . '.' . $ext;
        $target = __DIR__ . '/../assets/images/' . $fav_name;
        if (move_uploaded_file($_FILES['site_favicon']['tmp_name'], $target)) {
            upsertSetting($db, 'site_favicon', BASE_URL . '/assets/images/' . $fav_name);
        }
    }

    $message = "Settings updated successfully!";
}

$telegram_updates = [];
if (isset($_POST['get_telegram_updates'])) {
    $token_to_use = $_POST['telegram_bot_token'] ?? null;
    $telegram_updates = Telegram::getUpdates($token_to_use);
    if (isset($telegram_updates['error'])) {
        $message = "Error: " . $telegram_updates['error'];
    }
}

if (isset($_POST['test_telegram'])) {
    $resp = Telegram::sendTestMessage();
    $decoded = json_decode($resp, true);
    if ($decoded && isset($decoded['ok']) && $decoded['ok']) {
        $message = "Success! Test message sent to Telegram.";
    } else {
        $message = "Error: Failed to send test message. Check your Bot Token and Chat ID.";
    }
}

$stmt = $db->query("SELECT * FROM settings");
$settings_raw = $stmt->fetchAll();
$settings = [];
foreach ($settings_raw as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$base_path = str_replace('/admin/settings.php', '', $_SERVER['PHP_SELF']);
$site_url = $protocol . '://' . $host . $base_path;
?>

<div class="max-w-6xl mx-auto">
    <div class="mb-6 text-center md:text-left">
        <h2 class="text-2xl font-black text-white tracking-tight">System Configuration</h2>
        <p class="text-slate-400 text-sm mt-1">Global settings and platform branding</p>
    </div>

    <?php if ($message): ?>
        <div class="p-4 rounded-xl mb-6 flex items-start space-x-3 bg-green-500/10 border border-green-500/30 text-green-400 text-sm">
            <i class="fas fa-check-circle mt-0.5"></i>
            <div><?php echo htmlspecialchars($message); ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data" class="space-y-6 pb-12">
        <!-- Identity & Status -->
        <div class="glass-card p-6 md:p-8">
            <h3 class="text-xs font-black text-white uppercase tracking-widest mb-6 flex items-center">
                <i class="fas fa-fingerprint mr-2 text-cyan-400"></i> Identity & Status
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 px-1">Site Name</label>
                    <input type="text" name="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'SMM Panel'); ?>" class="w-full">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 px-1">Support Contact</label>
                    <input type="text" name="support_number" value="<?php echo htmlspecialchars($settings['support_number'] ?? ''); ?>" placeholder="+91..." class="w-full">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 px-1">System Status</label>
                    <select name="maintenance_mode" class="w-full">
                        <option value="0" <?php echo ($settings['maintenance_mode'] ?? '0') === '0' ? 'selected' : ''; ?>>Active (Online)</option>
                        <option value="1" <?php echo ($settings['maintenance_mode'] ?? '0') === '1' ? 'selected' : ''; ?>>Maintenance (Offline)</option>
                    </select>
                </div>
            </div>
            
            <div class="mt-6 pt-6 border-t border-white/5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 px-1">Announcement Text</label>
                        <div class="flex gap-3">
                            <select name="announcement_enabled" class="w-32">
                                <option value="1" <?php echo ($settings['announcement_enabled'] ?? '1') === '1' ? 'selected' : ''; ?>>ON</option>
                                <option value="0" <?php echo ($settings['announcement_enabled'] ?? '1') === '0' ? 'selected' : ''; ?>>OFF</option>
                            </select>
                            <input type="text" name="announcement_text" value="<?php echo htmlspecialchars($settings['announcement_text'] ?? ''); ?>" class="flex-1" placeholder="Banner message...">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 px-1">Site Logo</label>
                            <input type="file" name="site_logo" class="w-full !p-1.5 !text-[10px]">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 px-1">Favicon</label>
                            <input type="file" name="site_favicon" class="w-full !p-1.5 !text-[10px]">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- API & Payments -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- SMM Provider -->
            <div class="glass-card p-6">
                <h3 class="text-xs font-black text-white uppercase tracking-widest mb-6 flex items-center">
                    <i class="fas fa-server mr-2 text-cyan-400"></i> Provider API
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 px-1">API URL</label>
                        <input type="url" name="smm_api_url" value="<?php echo htmlspecialchars($settings['smm_api_url'] ?? ''); ?>" required class="w-full">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 px-1">API Key</label>
                        <input type="password" name="smm_api_key" value="<?php echo htmlspecialchars($settings['smm_api_key'] ?? ''); ?>" required class="w-full">
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 px-1">Profit %</label>
                            <input type="number" step="0.01" name="profit_percent" value="<?php echo htmlspecialchars($settings['profit_percent'] ?? 5); ?>" required class="w-full">
                        </div>
                        <div class="flex-1">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 px-1">Gateway</label>
                            <select name="active_gateway" class="w-full">
                                <option value="jzstore" <?php echo ($settings['active_gateway'] ?? '') === 'jzstore' ? 'selected' : ''; ?>>JZStore QR</option>
                                <option value="ekupi" <?php echo ($settings['active_gateway'] ?? '') === 'ekupi' ? 'selected' : ''; ?>>eKupi</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- JZStore Cash -->
            <div class="glass-card p-6">
                <h3 class="text-xs font-black text-white uppercase tracking-widest mb-6 flex items-center">
                    <i class="fas fa-qrcode mr-2 text-cyan-400"></i> JZStore QR Settings
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 px-1">User Token</label>
                        <input type="text" name="jzstore_token" value="<?php echo htmlspecialchars($settings['jzstore_token'] ?? ''); ?>" placeholder="Token from jzstore.in" class="w-full">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 px-1">Redirect URL</label>
                        <input type="url" name="jzstore_redirect_url" value="<?php echo htmlspecialchars($settings['jzstore_redirect_url'] ?? $site_url . '/payment_callback.php'); ?>" class="w-full text-xs">
                    </div>
                </div>
            </div>

            <!-- eKupi Settings -->
            <div class="glass-card p-6">
                <h3 class="text-xs font-black text-white uppercase tracking-widest mb-6 flex items-center">
                    <i class="fas fa-wallet mr-2 text-indigo-400"></i> eKupi Settings
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 px-1">API Key</label>
                        <input type="text" name="ekupi_key" value="<?php echo htmlspecialchars($settings['ekupi_key'] ?? ''); ?>" placeholder="GMC-..." class="w-full">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 px-1">Callback URL</label>
                        <input type="url" name="ekupi_redirect_url" value="<?php echo htmlspecialchars($settings['ekupi_redirect_url'] ?? $site_url . '/payment_callback.php'); ?>" class="w-full text-xs">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 px-1">Webhook Token (Security)</label>
                        <div class="flex gap-2">
                            <input type="text" id="ekupi_token_input" name="ekupi_webhook_token" value="<?php echo htmlspecialchars($settings['ekupi_webhook_token'] ?? ''); ?>" placeholder="Random string" class="flex-1">
                            <button type="button" onclick="generateToken()" class="bg-indigo-600/20 hover:bg-indigo-600 text-indigo-400 hover:text-white px-4 rounded-xl text-[10px] font-bold transition-all border border-indigo-600/30">Generate</button>
                        </div>
                    </div>
                    <div class="p-3 bg-indigo-400/5 border border-indigo-400/10 rounded-xl">
                        <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-widest mb-1">Your Webhook URL (Copy this to eKupi Dashboard)</label>
                        <div class="flex items-center gap-2">
                            <input type="text" readonly id="ekupi_webhook_url" value="<?php echo $site_url; ?>/ekupi_webhook.php?token=<?php echo htmlspecialchars($settings['ekupi_webhook_token'] ?? ''); ?>" class="bg-transparent !border-0 !p-0 text-[10px] text-indigo-300 font-mono flex-1">
                            <button type="button" onclick="copyToClipboard('ekupi_webhook_url')" class="text-indigo-400 hover:text-white"><i class="fas fa-copy text-xs"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Social & Notifications -->
        <div class="glass-card p-6 md:p-8">
            <h3 class="text-xs font-black text-white uppercase tracking-widest mb-6 flex items-center">
                <i class="fas fa-bell mr-2 text-cyan-400"></i> Telegram & Notifications
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 px-1">Bot Token</label>
                    <input type="text" name="telegram_bot_token" value="<?php echo htmlspecialchars($settings['telegram_bot_token'] ?? ''); ?>" placeholder="123456:ABC..." class="w-full">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 px-1">Admin Chat ID</label>
                    <input type="text" name="telegram_chat_id" value="<?php echo htmlspecialchars($settings['telegram_chat_id'] ?? ''); ?>" placeholder="-100..." class="w-full">
                </div>
            </div>
            
            <div class="flex flex-wrap gap-3">
                <button type="submit" name="get_telegram_updates" value="1" class="bg-white/5 hover:bg-white/10 text-white text-[10px] font-black py-2.5 px-5 rounded-xl uppercase tracking-widest transition-all">
                    Get Chat IDs
                </button>
                <button type="submit" name="test_telegram" value="1" class="bg-cyan-400/10 hover:bg-cyan-400 text-cyan-400 hover:text-white border border-cyan-400/20 text-[10px] font-black py-2.5 px-5 rounded-xl uppercase tracking-widest transition-all">
                    Send Test
                </button>
            </div>

            <?php if (!empty($telegram_updates) && isset($telegram_updates['result'])): ?>
            <div class="mt-6 glass overflow-hidden rounded-xl border border-white/5">
                <table class="w-full text-left text-[11px]">
                    <thead class="bg-white/5 text-slate-500 uppercase text-[9px] font-black">
                        <tr>
                            <th class="px-4 py-3">Chat ID</th>
                            <th class="px-4 py-2">Name</th>
                            <th class="px-4 py-2 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php 
                        $seen_chats = [];
                        foreach (array_reverse($telegram_updates['result']) as $update): 
                            $chat = $update['message']['chat'] ?? null;
                            if (!$chat || in_array($chat['id'], $seen_chats)) continue;
                            $seen_chats[] = $chat['id'];
                            $name = ($chat['title'] ?? '') ?: (($chat['first_name'] ?? '') . ' ' . ($chat['last_name'] ?? ''));
                        ?>
                        <tr>
                            <td class="px-4 py-3 font-mono text-cyan-400"><?php echo $chat['id']; ?></td>
                            <td class="px-4 py-3 text-white"><?php echo htmlspecialchars($name); ?></td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" onclick="setChatId('<?php echo $chat['id']; ?>')" class="text-[9px] font-black text-cyan-400 uppercase tracking-widest hover:text-white">Use</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Google Auth -->
        <div class="glass-card p-6 md:p-8">
            <h3 class="text-xs font-black text-white uppercase tracking-widest mb-6 flex items-center">
                <i class="fab fa-google mr-2 text-red-400"></i> Google Social Login
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 px-1">Login Status</label>
                    <select name="google_auth_enabled" class="w-full">
                        <option value="0" <?php echo ($settings['google_auth_enabled'] ?? '0') === '0' ? 'selected' : ''; ?>>Disabled</option>
                        <option value="1" <?php echo ($settings['google_auth_enabled'] ?? '0') === '1' ? 'selected' : ''; ?>>Enabled</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 px-1">Client ID</label>
                    <input type="text" name="google_client_id" value="<?php echo htmlspecialchars($settings['google_client_id'] ?? ''); ?>" class="w-full">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 px-1">Client Secret</label>
                    <input type="password" name="google_client_secret" value="<?php echo htmlspecialchars($settings['google_client_secret'] ?? ''); ?>" class="w-full">
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-center pt-6">
            <button type="submit" class="btn-primary text-white py-4 px-12 rounded-2xl shadow-2xl active:scale-95 uppercase tracking-widest font-black">
                <i class="fas fa-save mr-2"></i> Save All Settings
            </button>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
    function generateToken() {
        const token = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
        document.getElementById('ekupi_token_input').value = token;
        updateWebhookUrl(token);
    }

    function updateWebhookUrl(token) {
        const baseUrl = '<?php echo $site_url; ?>';
        document.getElementById('ekupi_webhook_url').value = baseUrl + '/ekupi_webhook.php?token=' + token;
    }

    function copyToClipboard(id) {
        const copyText = document.getElementById(id);
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(copyText.value);
        
        const btn = event.currentTarget;
        const originalIcon = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check text-green-400"></i>';
        setTimeout(() => { btn.innerHTML = originalIcon; }, 2000);
    }

    function setChatId(id) {
        const input = document.getElementsByName('telegram_chat_id')[0];
        if (input) {
            input.value = id;
            input.focus();
            input.classList.add('ring-4', 'ring-cyan-500/30', 'border-cyan-500');
            setTimeout(() => {
                input.classList.remove('ring-4', 'ring-cyan-500/30', 'border-cyan-500');
            }, 2000);
            
            input.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
</script>
