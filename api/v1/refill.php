<?php
// api/v1/refill.php

require_once 'api_base.php';
require_once '../../includes/SmmApi.php';

$order_id = $_POST['order_id'] ?? $_POST['order'] ?? null;

if (!$order_id) {
    sendError('Missing order_id');
}

$stmt = $db->prepare("SELECT api_order_id, status FROM orders WHERE user_id = ? AND id = ?");
$stmt->execute([$user_id, $order_id]);
$order = $stmt->fetch();

if (!$order) {
    sendError('Incorrect order ID');
}

if ($order['status'] !== 'Completed' && $order['status'] !== 'Partial') {
    sendError('Order is not eligible for refill');
}

$api = new SmmApi();
$api_response = $api->refill($order['api_order_id']);

if (isset($api_response->error)) {
    sendError($api_response->error);
}

if (isset($api_response->refill)) {
    sendResponse([
        'status' => 'success',
        'refill' => $api_response->refill
    ]);
}

sendError('Failed to initiate refill');
