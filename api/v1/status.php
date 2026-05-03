<?php
// api/v1/status.php

require_once 'api_base.php';

if (isset($_POST['order_ids'])) {
    $order_ids = explode(',', $_POST['order_ids']);
    $placeholders = str_repeat('?,', count($order_ids) - 1) . '?';
    
    // We must check if these orders belong to the user
    $query = "SELECT id, charge, status, remains FROM orders WHERE user_id = ? AND id IN ($placeholders)";
    $params = array_merge([$user_id], $order_ids);
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
    
    $result = [];
    foreach ($orders as $order) {
        $result[$order['id']] = [
            'status' => $order['status'],
            'charge' => (float)$order['charge'],
            'remains' => (int)$order['remains']
        ];
    }
    
    // Also include error for IDs not found
    foreach ($order_ids as $id) {
        if (!isset($result[$id])) {
            $result[$id] = ['error' => 'Incorrect order ID'];
        }
    }
    
    sendResponse($result);
}

$order_id = $_POST['order_id'] ?? $_POST['order'] ?? null;

if (!$order_id) {
    sendError('Missing order_id');
}

$stmt = $db->prepare("SELECT id, charge, status, remains FROM orders WHERE user_id = ? AND id = ?");
$stmt->execute([$user_id, $order_id]);
$order = $stmt->fetch();

if (!$order) {
    sendError('Incorrect order ID');
}

sendResponse([
    'status' => $order['status'],
    'charge' => (float)$order['charge'],
    'remains' => (int)$order['remains']
]);
