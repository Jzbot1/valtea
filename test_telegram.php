<?php
require_once 'config/database.php';
require_once 'includes/Telegram.php';

echo "<h2>Telegram Notification Test</h2>";

$db = Database::getInstance();
$stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('telegram_bot_token', 'telegram_chat_id')");
$settings = [];
foreach ($stmt->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$token = $settings['telegram_bot_token'] ?? '';
$chat = $settings['telegram_chat_id'] ?? '';

if (!$token || !$chat) {
    echo "<p style='color:red;'>Error: Telegram Bot Token or Chat ID is not set in Admin Settings!</p>";
    exit;
}

echo "<p>Using Token: <code>" . substr($token, 0, 10) . "...</code></p>";
echo "<p>Using Chat ID: <code>$chat</code></p>";

echo "<p>Sending test message...</p>";

$res = Telegram::sendOrderNotification(
    "TEST-123", 
    "Test SMM Service", 
    "https://example.com", 
    1000, 
    5.50, 
    "admin@example.com"
);

if ($res) {
    $decoded = json_decode($res, true);
    if (isset($decoded['ok']) && $decoded['ok'] === true) {
        echo "<h3 style='color:green;'>SUCCESS! Telegram message sent.</h3>";
        echo "<pre>" . json_encode($decoded, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<h3 style='color:red;'>FAILED! API returned an error.</h3>";
        echo "<pre>" . json_encode($decoded, JSON_PRETTY_PRINT) . "</pre>";
    }
} else {
    echo "<h3 style='color:red;'>FAILED! No response from Telegram API. Check your internet or cURL.</h3>";
}

echo "<br><a href='admin/settings.php'>Go to Settings</a>";
