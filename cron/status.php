<?php
// cron/status.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/SmmApi.php';

$db = Database::getInstance();
$api = new SmmApi();

// Get orders that are not Completed, Canceled, or Partial
$stmt = $db->query("SELECT id, api_order_id FROM orders WHERE status NOT IN ('Completed', 'Canceled', 'Partial') AND api_order_id IS NOT NULL LIMIT 100");
$orders = $stmt->fetchAll();

if (empty($orders)) {
    die("No pending orders");
}

$api_order_ids = array_column($orders, 'api_order_id');
$api_order_map = [];
foreach ($orders as $order) {
    $api_order_map[$order['api_order_id']] = $order['id'];
}

$response = $api->multiStatus($api_order_ids);

if (isset($response->error)) {
    die("API Error: " . $response->error);
}

foreach ($response as $api_order_id => $data) {
    if (isset($data->error)) continue;
    
    $local_order_id = $api_order_map[$api_order_id] ?? null;
    if (!$local_order_id) continue;
    
    $status = $data->status ?? 'Pending';
    $remains = $data->remains ?? 0;
    
    $updateStmt = $db->prepare("UPDATE orders SET status = ?, remains = ? WHERE id = ?");
    $updateStmt->execute([$status, $remains, $local_order_id]);
}

// Record last run
$last_run = date('Y-m-d H:i:s');
$stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('last_cron_status_run', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
$stmt->execute([$last_run, $last_run]);

echo "Order statuses updated successfully. Last run: " . $last_run;
