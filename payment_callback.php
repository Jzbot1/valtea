<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/EkupiGateway.php';
require_once __DIR__ . '/includes/JzstoreGateway.php';
require_once __DIR__ . '/includes/Auth.php';

Auth::checkLogin();

$db = Database::getInstance();
EkupiGateway::ensureSchema($db);

$stmt = $db->query("SELECT setting_key, setting_value FROM settings");
$all_settings = [];
foreach ($stmt->fetchAll() as $row) {
    $all_settings[$row['setting_key']] = $row['setting_value'];
}

$active_gateway = $all_settings['active_gateway'] ?? 'ekupi';

// eKupi often sends order_id or client_txn_id
$clientTxnId = trim((string)($_GET['client_txn_id'] ?? $_POST['client_txn_id'] ?? $_GET['order_id'] ?? $_GET['txn_id'] ?? ''));

if ($clientTxnId === '') {
    error_log("Payment Callback Error: Missing client_txn_id. GET: " . json_encode($_GET));
    header('Location: ' . BASE_URL . '/add_funds?status=failed');
    exit;
}

$stmt = $db->prepare("SELECT * FROM payment_orders WHERE client_txn_id = ?");
$stmt->execute([$clientTxnId]);
$paymentOrder = $stmt->fetch();

if (!$paymentOrder) {
    error_log("Payment Callback Error: Order not found for ID: " . $clientTxnId);
    header('Location: ' . BASE_URL . '/add_funds?status=failed');
    exit;
}

if ($active_gateway === 'jzstore') {
    $gw_settings = JzstoreGateway::getSettings($db);
    $check = JzstoreGateway::checkOrderStatus($gw_settings, $clientTxnId);
    
    // JZStore structure: {"status": true, "result": {"status": "SUCCESS/COMPLETED", ...}}
    $apiStatus = $check['data']['status'] ?? false;
    $orderResult = $check['data']['result'] ?? [];
    $orderStatus = strtolower((string)($orderResult['status'] ?? ''));
    
    if ($apiStatus === true && in_array($orderStatus, ['success', 'completed', 'success_scan', 'scan_pay'])) {
        $status = 'success';
    } else {
        $status = 'failed';
    }
    
    $utr = (string)($orderResult['utr'] ?? '');
} else {
    $gw_settings = EkupiGateway::getSettings($db);
    $check = EkupiGateway::checkOrderStatus($gw_settings, $clientTxnId);
    
    // eKupi success statuses can be 'SUCCESS', 'COMPLETED', etc.
    $rawStatus = strtolower((string)($check['data']['data']['status'] ?? ''));
    $status = in_array($rawStatus, ['success', 'completed', 'success_scan', 'scan_pay']) ? 'success' : $rawStatus;
    $utr = (string)($check['data']['data']['utr'] ?? '');
}

if (!$check['ok']) {
    error_log("Payment Callback Error: Gateway check failed. " . ($check['error'] ?? 'Unknown error'));
    header('Location: ' . BASE_URL . '/add_funds?status=failed');
    exit;
}

if ($status === 'success') {
    EkupiGateway::finalizeSuccess($db, $clientTxnId, $utr, json_encode($check['data']));
    header('Location: ' . BASE_URL . '/add_funds?status=success');
    exit;
}

error_log("Payment Callback: Order status is " . $status . " for ID: " . $clientTxnId);
EkupiGateway::markFailed($db, $clientTxnId, json_encode($check['data']));
header('Location: ' . BASE_URL . '/add_funds?status=failed');
exit;

