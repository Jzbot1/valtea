<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/EkupiGateway.php';

$db = Database::getInstance();
EkupiGateway::ensureSchema($db);
$settings = EkupiGateway::getSettings($db);

header('Content-Type: application/json');

$token = trim((string)($_GET['token'] ?? ''));
if ($settings['webhook_token'] !== '' && !hash_equals($settings['webhook_token'], $token)) {
    http_response_code(403);
    error_log("Ekupi Webhook Error: Invalid Token ($token)");
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    http_response_code(400);
    error_log("Ekupi Webhook Error: Invalid JSON Payload - " . substr($raw, 0, 500));
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$clientTxnId = trim((string)($payload['client_txn_id'] ?? ''));
if ($clientTxnId === '') {
    http_response_code(400);
    error_log("Ekupi Webhook Error: Missing client_txn_id in payload");
    echo json_encode(['success' => false, 'message' => 'Missing client_txn_id']);
    exit;
}

// Security: Always verify the status with the gateway directly before crediting
$statusCheck = EkupiGateway::checkOrderStatus($settings, $clientTxnId);
if (!$statusCheck['ok']) {
    http_response_code(202); // Accepted but not processed
    error_log("Ekupi Webhook Info: Order verification failed for $clientTxnId - " . $statusCheck['error']);
    echo json_encode(['success' => false, 'message' => 'Unable to verify status currently']);
    exit;
}

$verified = $statusCheck['data'];
$status = strtolower((string)($verified['data']['status'] ?? ''));
$utr = (string)($verified['data']['utr'] ?? ($payload['utr'] ?? ''));

if ($status === 'success') {
    if (EkupiGateway::finalizeSuccess($db, $clientTxnId, $utr, json_encode($verified))) {
        echo json_encode(['success' => true, 'message' => 'Processed successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Internal processing error']);
    }
    exit;
}

EkupiGateway::markFailed($db, $clientTxnId, json_encode($verified));
echo json_encode(['success' => true, 'message' => 'Recorded status: ' . $status]);
