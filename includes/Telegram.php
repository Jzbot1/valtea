<?php
// includes/Telegram.php

class Telegram {
    public static function sendOrderNotification($orderId, $serviceName, $link, $quantity, $charge, $userEmail) {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('telegram_bot_token', 'telegram_chat_id')");
        $settings = [];
        foreach ($stmt->fetchAll() as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        $botToken = $settings['telegram_bot_token'] ?? '';
        $chatId = $settings['telegram_chat_id'] ?? '';

        if (empty($botToken) || empty($chatId)) {
            return false;
        }

        $text = "🔔 *New Order Received*\n\n" .
                "🛒 *Order ID:* #$orderId\n" .
                "👤 *User:* $userEmail\n" .
                "📦 *Service:* $serviceName\n" .
                "🔗 *Link:* $link\n" .
                "🔢 *Quantity:* $quantity\n" .
                "💰 *Charge:* $" . number_format($charge, 4);

        return self::sendMessage($botToken, $chatId, $text);
    }

    public static function getUpdates($customToken = null) {
        $botToken = $customToken;
        
        if (empty($botToken)) {
            $db = Database::getInstance();
            $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'telegram_bot_token'");
            $botToken = $stmt->fetchColumn();
        }

        if (empty($botToken)) {
            return ['error' => 'Bot token not set'];
        }

        $url = "https://api.telegram.org/bot" . $botToken . "/getUpdates";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }

    public static function sendTestMessage() {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('telegram_bot_token', 'telegram_chat_id')");
        $settings = [];
        foreach ($stmt->fetchAll() as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        $botToken = $settings['telegram_bot_token'] ?? '';
        $chatId = $settings['telegram_chat_id'] ?? '';

        if (empty($botToken) || empty($chatId)) {
            return ['error' => 'Bot token or Chat ID not set'];
        }

        $text = "🚀 *Telegram Test Notification*\n\n" .
                "This is a demo notification from your SMM Panel admin area.\n\n" .
                "✅ *Status:* Bot is connected successfully!";

        return self::sendMessage($botToken, $chatId, $text);
    }

    private static function sendMessage($botToken, $chatId, $text) {
        $url = "https://api.telegram.org/bot" . $botToken . "/sendMessage";
        
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown'
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        
        return $response;
    }
}
