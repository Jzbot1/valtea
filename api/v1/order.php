<?php
// api/v1/order.php

require_once 'api_base.php';
require_once '../../includes/SmmApi.php';
require_once '../../includes/Telegram.php';

$service_id = $_POST['service'] ?? null;
$link = $_POST['link'] ?? null;
$quantity = $_POST['quantity'] ?? null;

if (!$service_id || !$link || !$quantity) {
    sendError('Missing required parameters (service, link, quantity)');
}

if (!is_numeric($quantity) || $quantity <= 0) {
    sendError('Invalid quantity');
}

$stmt = $db->prepare("SELECT * FROM services WHERE id = ? AND status = 'active'");
$stmt->execute([$service_id]);
$service = $stmt->fetch();

if (!$service) {
    sendError('Invalid service ID');
}

if ($quantity < $service['min'] || $quantity > $service['max']) {
    sendError("Quantity must be between {$service['min']} and {$service['max']}");
}

$charge = ($service['selling_price'] / 1000) * $quantity;
$api_charge = ($service['api_rate'] / 1000) * $quantity;

if ($user_balance < $charge) {
    sendError('Insufficient balance');
}

try {
    $db->beginTransaction();

    // Deduct balance
    $new_balance = $user_balance - $charge;
    $stmt = $db->prepare("UPDATE users SET balance = ? WHERE id = ?");
    $stmt->execute([$new_balance, $user_id]);

    // Insert transaction
    $stmt = $db->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'debit', ?)");
    $stmt->execute([$user_id, $charge, "Order placed for service ID $service_id"]);

    // Call SMM Provider API
    $api = new SmmApi();
    $api_response = $api->order([
        'service' => $service['api_service_id'],
        'link' => $link,
        'quantity' => $quantity
    ]);

    if (isset($api_response->error)) {
        throw new Exception("Provider API Error: " . $api_response->error);
    }

    $api_order_id = $api_response->order ?? null;

    if (!$api_order_id) {
        throw new Exception("Failed to get order ID from provider");
    }

    // Insert order
    $stmt = $db->prepare("INSERT INTO orders (user_id, service_id, api_order_id, link, quantity, charge, api_charge, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
    $stmt->execute([$user_id, $service_id, $api_order_id, $link, $quantity, $charge, $api_charge]);
    $order_id = $db->lastInsertId();

    $db->commit();

    // Send Telegram Notification
    Telegram::sendOrderNotification($order_id, $service['name'], $link, $quantity, $charge, $user_email);

    sendResponse([
        'status' => 'success',
        'order_id' => $order_id
    ]);

} catch (Exception $e) {
    $db->rollBack();
    sendError($e->getMessage());
}
