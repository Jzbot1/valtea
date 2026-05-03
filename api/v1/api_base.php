<?php
// api/v1/api_base.php

require_once '../../config/database.php';

header('Content-Type: application/json');

function sendResponse($data) {
    echo json_encode($data);
    exit;
}

function sendError($message) {
    sendResponse(['status' => 'error', 'message' => $message]);
}

$headers = getallheaders();
$api_key = $_POST['api_key'] ?? $headers['api_key'] ?? $headers['Api-Key'] ?? null;

if (!$api_key) {
    sendError('Missing API key');
}

$db = Database::getInstance();
$stmt = $db->prepare("SELECT id, email, balance FROM users WHERE api_key = ?");
$stmt->execute([$api_key]);
$user = $stmt->fetch();

if (!$user) {
    sendError('Invalid API key');
}

$user_id = $user['id'];
$user_email = $user['email'];
$user_balance = $user['balance'];
