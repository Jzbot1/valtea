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

<div class="glass rounded-2xl p-6 md:p-8 shadow-2xl max-w-5xl mb-8">
    <h2 class="text-2xl font-bold text-white mb-6">Site Configuration & Branding</h2>

    <?php if ($message): ?>
        <div class="p-4 rounded-lg mb-6 bg-green-500/10 border border-green-500/50 text-green-400">
            <i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
        <!-- Site Branding Section -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 p-6 bg-slate-800/30 rounded-xl border border-slate-700/50">
            <div class="md:col-span-3"><h3 class="text-lg font-semibold text-white">Branding & Identity</h3></div>
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Site Name</label>
                <input type="text" name="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'SMM Panel'); ?>" class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Support WhatsApp/Mobile</label>
                <input type="text" name="support_number" value="<?php echo htmlspecialchars($settings['support_number'] ?? ''); ?>" placeholder="+91..." class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Maintenance Mode</label>
                <select name="maintenance_mode" class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500">
                    <option value="0" <?php echo ($settings['maintenance_mode'] ?? '0') === '0' ? 'selected' : ''; ?>>Active (Online)</option>
                    <option value="1" <?php echo ($settings['maintenance_mode'] ?? '0') === '1' ? 'selected' : ''; ?>>Maintenance (Offline)</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Active Payment Gateway</label>
                <select name="active_gateway" class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500">
                    <option value="ekupi" <?php echo ($settings['active_gateway'] ?? 'ekupi') === 'ekupi' ? 'selected' : ''; ?>>eKupi (Primary)</option>
                    <option value="jzstore" <?php echo ($settings['active_gateway'] ?? 'ekupi') === 'jzstore' ? 'selected' : ''; ?>>JZStore Cash</option>
                </select>
            </div>

            <div class="md:col-span-3 border-t border-slate-700/50 pt-6 mt-2">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-1">
                        <label class="block text-sm font-medium text-slate-300 mb-2">Announcement Banner</label>
                        <select name="announcement_enabled" class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500">
                            <option value="1" <?php echo ($settings['announcement_enabled'] ?? '1') === '1' ? 'selected' : ''; ?>>Enabled</option>
                            <option value="0" <?php echo ($settings['announcement_enabled'] ?? '1') === '0' ? 'selected' : ''; ?>>Disabled</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-300 mb-2">Announcement Text</label>
                        <input type="text" name="announcement_text" value="<?php echo htmlspecialchars($settings['announcement_text'] ?? 'Welcome to our premium SMM panel!'); ?>" class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500">
                    </div>
                </div>
            </div>

            <div class="md:col-span-1">
                <label class="block text-sm font-medium text-slate-300 mb-2">Site Logo</label>
                <input type="file" name="site_logo" accept="image/*" class="w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700 cursor-pointer">
                <?php if (!empty($settings['site_logo'])): ?>
                    <img src="<?php echo $settings['site_logo']; ?>" class="mt-2 h-12 rounded bg-slate-900 p-1">
                <?php endif; ?>
            </div>

            <div class="md:col-span-1">
                <label class="block text-sm font-medium text-slate-300 mb-2">Favicon (32x32)</label>
                <input type="file" name="site_favicon" accept="image/x-icon,image/png" class="w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700 cursor-pointer">
                <?php if (!empty($settings['site_favicon'])): ?>
                    <img src="<?php echo $settings['site_favicon']; ?>" class="mt-2 h-8 w-8 rounded bg-slate-900 p-1">
                <?php endif; ?>
            </div>

            <div class="md:col-span-3 border-t border-slate-700/50 pt-6 mt-2">
                <div class="glass border border-slate-700/50 rounded-3xl p-8 shadow-2xl mb-8">
                    <h3 class="text-xl font-bold text-white mb-8 flex items-center">
                        <span class="w-10 h-10 bg-indigo-600/20 text-indigo-400 rounded-xl flex items-center justify-center mr-4">
                            <i class="fas fa-share-alt"></i>
                        </span>
                        Social Links
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-2">Facebook URL</label>
                            <input type="url" name="facebook_url" value="<?php echo htmlspecialchars($settings['facebook_url'] ?? ''); ?>" placeholder="https://facebook.com/..." class="w-full bg-slate-900/50 border border-slate-700 rounded-lg px-4 py-2 text-white text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-2">Twitter URL</label>
                            <input type="url" name="twitter_url" value="<?php echo htmlspecialchars($settings['twitter_url'] ?? ''); ?>" placeholder="https://twitter.com/..." class="w-full bg-slate-900/50 border border-slate-700 rounded-lg px-4 py-2 text-white text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-2">Instagram URL</label>
                            <input type="url" name="instagram_url" value="<?php echo htmlspecialchars($settings['instagram_url'] ?? ''); ?>" placeholder="https://instagram.com/..." class="w-full bg-slate-900/50 border border-slate-700 rounded-lg px-4 py-2 text-white text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-2">Telegram URL</label>
                            <input type="url" name="telegram_url" value="<?php echo htmlspecialchars($settings['telegram_url'] ?? ''); ?>" placeholder="https://t.me/..." class="w-full bg-slate-900/50 border border-slate-700 rounded-lg px-4 py-2 text-white text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                    </div>
                </div>

                <!-- SMTP Configuration -->
                <div class="glass border border-slate-700/50 rounded-3xl p-8 shadow-2xl">
                    <h3 class="text-xl font-bold text-white mb-8 flex items-center">
                        <span class="w-10 h-10 bg-indigo-600/20 text-indigo-400 rounded-xl flex items-center justify-center mr-4">
                            <i class="fas fa-envelope"></i>
                        </span>
                        SMTP & Email Settings
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">SMTP Host</label>
                            <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>" placeholder="smtp.gmail.com" class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-indigo-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">SMTP Port</label>
                            <input type="text" name="smtp_port" value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '465'); ?>" placeholder="465 or 587" class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-indigo-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">SMTP User</label>
                            <input type="text" name="smtp_user" value="<?php echo htmlspecialchars($settings['smtp_user'] ?? ''); ?>" placeholder="your-email@gmail.com" class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-indigo-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">SMTP Password</label>
                            <input type="password" name="smtp_pass" value="<?php echo htmlspecialchars($settings['smtp_pass'] ?? ''); ?>" placeholder="••••••••" class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-indigo-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">From Email</label>
                            <input type="email" name="smtp_from_email" value="<?php echo htmlspecialchars($settings['smtp_from_email'] ?? ''); ?>" placeholder="noreply@mirakistore.com" class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-indigo-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">From Name</label>
                            <input type="text" name="smtp_from_name" value="<?php echo htmlspecialchars($settings['smtp_from_name'] ?? ''); ?>" placeholder="Support" class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-indigo-500 transition-all">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <div class="space-y-6">
                <h3 class="text-lg font-semibold text-white border-b border-slate-700/50 pb-2">API Settings</h3>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Global Profit Percentage (%)</label>
                    <input type="number" step="0.01" name="profit_percent" value="<?php echo htmlspecialchars($settings['profit_percent'] ?? 5); ?>" required class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-300 mb-2">SMM Provider API URL</label>
                    <input type="url" name="smm_api_url" value="<?php echo htmlspecialchars($settings['smm_api_url'] ?? ''); ?>" required class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-300 mb-2">SMM Provider API Key</label>
                    <input type="text" name="smm_api_key" value="<?php echo htmlspecialchars($settings['smm_api_key'] ?? ''); ?>" required class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500">
                </div>
            </div>

            <div class="space-y-6">
                <h3 class="text-lg font-semibold text-white border-b border-slate-700/50 pb-2">eKupi Gateway Details</h3>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-300 mb-2">eKupi API Key</label>
                    <input type="text" name="ekupi_key" value="<?php echo htmlspecialchars($settings['ekupi_key'] ?? ''); ?>" placeholder="GMC-XXXX-XXXXXX-XXXXXX" class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Payment Redirect URL</label>
                    <input type="url" name="ekupi_redirect_url" value="<?php echo htmlspecialchars($settings['ekupi_redirect_url'] ?? $site_url . '/payment_callback.php'); ?>" class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500">
                </div>
            </div>
            </div>
        </div>

        <!-- Google Auth Configuration -->
        <div class="glass border border-slate-700/50 rounded-3xl p-8 shadow-2xl mb-8">
            <h3 class="text-xl font-bold text-white mb-8 flex items-center">
                <span class="w-10 h-10 bg-red-600/20 text-red-400 rounded-xl flex items-center justify-center mr-4">
                    <i class="fab fa-google"></i>
                </span>
                Google Authentication (Social Login)
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Google Login Status</label>
                    <select name="google_auth_enabled" class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500">
                        <option value="0" <?php echo ($settings['google_auth_enabled'] ?? '0') === '0' ? 'selected' : ''; ?>>Disabled</option>
                        <option value="1" <?php echo ($settings['google_auth_enabled'] ?? '0') === '1' ? 'selected' : ''; ?>>Enabled</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Client ID</label>
                    <input type="text" name="google_client_id" value="<?php echo htmlspecialchars($settings['google_client_id'] ?? ''); ?>" placeholder="Enter Google Client ID" class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-indigo-500 transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Client Secret</label>
                    <input type="password" name="google_client_secret" value="<?php echo htmlspecialchars($settings['google_client_secret'] ?? ''); ?>" placeholder="Enter Google Client Secret" class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-indigo-500 transition-all">
                </div>
                <div class="md:col-span-2 p-4 bg-indigo-500/10 border border-indigo-500/20 rounded-xl">
                    <p class="text-[10px] text-slate-400 font-bold uppercase mb-2">Redirect URI (Use this in Google Console):</p>
                    <code class="text-xs text-indigo-400 font-mono break-all"><?php echo FULL_URL; ?>/google_callback</code>
                </div>
            </div>
        </div>

        <div class="mb-8 border-t border-slate-700/50 pt-8">
            <h3 class="text-lg font-semibold text-white mb-6">JZStore Gateway Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">JZStore User Token</label>
                    <input type="text" name="jzstore_token" value="<?php echo htmlspecialchars($settings['jzstore_token'] ?? ''); ?>" placeholder="509ffd..." class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500 mb-4">

                    <label class="block text-sm font-medium text-slate-300 mb-2">JZStore Base URL</label>
                    <input type="url" name="jzstore_base_url" value="<?php echo htmlspecialchars($settings['jzstore_base_url'] ?? 'https://cash.free.jzstore.in'); ?>" class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">JZStore Redirect URL</label>
                    <input type="url" name="jzstore_redirect_url" value="<?php echo htmlspecialchars($settings['jzstore_redirect_url'] ?? $site_url . '/payment_callback.php'); ?>" class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500">
                    <p class="text-xs text-slate-500 mt-2">Required for processing successful transactions back to your site.</p>
                </div>
            </div>
        </div>

        <div class="mb-8 border-t border-slate-700/50 pt-8">
            <h3 class="text-lg font-semibold text-white mb-6">Telegram Order Notifications</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 bg-indigo-500/5 p-6 rounded-xl border border-indigo-500/20">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Bot Token</label>
                    <input type="text" name="telegram_bot_token" value="<?php echo htmlspecialchars($settings['telegram_bot_token'] ?? ''); ?>" placeholder="123456:ABC-DEF..." class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Chat ID</label>
                    <input type="text" name="telegram_chat_id" value="<?php echo htmlspecialchars($settings['telegram_chat_id'] ?? ''); ?>" placeholder="-100123456789" class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500">
                </div>
                <div class="md:col-span-2 text-xs text-slate-400">
                    <i class="fas fa-info-circle mr-1"></i> Notifications will be sent to this Telegram chat whenever a new order is placed.
                </div>
                
                <div class="md:col-span-2 flex flex-wrap gap-4 mt-4">
                    <button type="submit" name="get_telegram_updates" value="1" class="bg-slate-700 hover:bg-slate-600 text-white text-sm font-medium py-2 px-4 rounded-lg transition-colors">
                        <i class="fas fa-search mr-2"></i> Get Latest Chat IDs
                    </button>
                    <button type="submit" name="test_telegram" value="1" class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium py-2 px-4 rounded-lg transition-colors">
                        <i class="fas fa-paper-plane mr-2"></i> Send Test Message
                    </button>
                </div>

                <?php if (!empty($telegram_updates) && isset($telegram_updates['result'])): ?>
                <div class="md:col-span-2 mt-6 overflow-hidden rounded-lg border border-slate-700">
                    <table class="w-full text-left text-sm text-slate-300">
                        <thead class="bg-slate-800 text-slate-400 uppercase text-[10px]">
                            <tr>
                                <th class="px-4 py-2">Chat ID</th>
                                <th class="px-4 py-2">Name</th>
                                <th class="px-4 py-2">Last Message</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700 bg-slate-900/50">
                            <?php 
                            $seen_chats = [];
                            foreach (array_reverse($telegram_updates['result']) as $update): 
                                $chat = $update['message']['chat'] ?? null;
                                if (!$chat || in_array($chat['id'], $seen_chats)) continue;
                                $seen_chats[] = $chat['id'];
                                $name = ($chat['title'] ?? '') ?: (($chat['first_name'] ?? '') . ' ' . ($chat['last_name'] ?? ''));
                            ?>
                            <tr>
                                <td class="px-4 py-2 font-mono text-indigo-400"><?php echo $chat['id']; ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($name); ?> (<?php echo $chat['type']; ?>)</td>
                                <td class="px-4 py-2 truncate max-w-[200px]"><?php echo htmlspecialchars($update['message']['text'] ?? ''); ?></td>
                                <td class="px-4 py-2 text-right">
                                    <button type="button" onclick="setChatId('<?php echo $chat['id']; ?>')" class="bg-indigo-600/20 hover:bg-indigo-600 text-indigo-400 hover:text-white text-[10px] font-bold py-1 px-2 rounded transition-all">
                                        USE
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($seen_chats)): ?>
                            <tr>
                                <td colspan="4" class="px-4 py-4 text-center text-slate-500">No recent messages found. Send a message to your bot first!</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-8 flex justify-center">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-4 px-12 rounded-xl transition-all shadow-xl shadow-indigo-600/20 active:scale-[0.98]">
                <i class="fas fa-save mr-2"></i> Save All Settings
            </button>
        </div>
    </form>
</div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
    function setChatId(id) {
        const input = document.getElementsByName('telegram_chat_id')[0];
        if (input) {
            input.value = id;
            input.focus();
            input.classList.add('ring-4', 'ring-indigo-500/30', 'border-indigo-500');
            setTimeout(() => {
                input.classList.remove('ring-4', 'ring-indigo-500/30', 'border-indigo-500');
            }, 2000);
            
            // Optional: Smooth scroll to the input
            input.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
</script>
