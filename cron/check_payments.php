<?php
// cron/check_payments.php
// This script should be run every 5-10 minutes to verify pending payments.

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/EkupiGateway.php';
require_once __DIR__ . '/../includes/JzstoreGateway.php';

$db = Database::getInstance();

// Fetch site settings
$stmt = $db->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
foreach ($stmt->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$active_gateway = $settings['active_gateway'] ?? 'ekupi';

// Fetch orders that are 'pending' or 'created' and not older than 24 hours
// But only check those created at least 5 minutes ago to give the user time to finish
$stmt = $db->prepare("
    SELECT * FROM payment_orders 
    WHERE status IN ('pending', 'created') 
    AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    AND created_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    LIMIT 20
");
$stmt->execute();
$pendingOrders = $stmt->fetchAll();

if (empty($pendingOrders)) {
    echo "No pending payments to check.\n";
    exit;
}

foreach ($pendingOrders as $order) {
    echo "Checking Order ID: " . $order['client_txn_id'] . "... ";
    
    $status = 'failed';
    $utr = '';
    $checkResponse = null;

    if ($active_gateway === 'jzstore') {
        $gw_settings = JzstoreGateway::getSettings($db);
        $check = JzstoreGateway::checkOrderStatus($gw_settings, $order['client_txn_id']);
        
        if ($check['ok']) {
            $checkResponse = $check['data'];
            $apiStatus = $checkResponse['status'] ?? false;
            $orderResult = $checkResponse['result'] ?? [];
            $orderStatus = strtolower((string)($orderResult['status'] ?? ''));
            
            if ($apiStatus === true && in_array($orderStatus, ['success', 'completed', 'success_scan', 'scan_pay'])) {
                $status = 'success';
            }
            $utr = (string)($orderResult['utr'] ?? '');
        }
    } else {
        // Default to eKupi
        $gw_settings = EkupiGateway::getSettings($db);
        $check = EkupiGateway::checkOrderStatus($gw_settings, $order['client_txn_id']);
        
        if ($check['ok']) {
            $checkResponse = $check['data'];
            $rawStatus = strtolower((string)($checkResponse['data']['status'] ?? ''));
            if (in_array($rawStatus, ['success', 'completed', 'success_scan', 'scan_pay'])) {
                $status = 'success';
            }
            $utr = (string)($checkResponse['data']['utr'] ?? '');
        }
    }

    if ($status === 'success') {
        if (EkupiGateway::finalizeSuccess($db, $order['client_txn_id'], $utr, json_encode($checkResponse))) {
            echo "SUCCESS: Credited balance.\n";
        } else {
            echo "ERROR: Failed to finalize success.\n";
        }
    } else {
        // If it's still not successful after some time, we don't mark it failed yet, 
        // just let it be until it hits the 24 hour limit or until user completes it.
        // However, if the gateway explicitly says it failed, we can mark it.
        echo "Still pending/failed.\n";
    }
}
