<?php
require_once 'config/database.php';
require_once 'includes/Auth.php';

$db = Database::getInstance();
$stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('google_auth_enabled', 'google_client_id', 'google_client_secret')");
$settings = [];
foreach ($stmt->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

if (($settings['google_auth_enabled'] ?? '0') !== '1') {
    die("Google Login is disabled.");
}

$code = $_GET['code'] ?? null;
if (!$code) {
    header("Location: login");
    exit;
}

$clientId = $settings['google_client_id'];
$clientSecret = $settings['google_client_secret'];
$redirectUri = FULL_URL . '/google_callback';

if (Auth::handleGoogleCallback($code, $clientId, $clientSecret, $redirectUri)) {
    header("Location: index");
} else {
    header("Location: login?error=google_failed");
}
exit;
