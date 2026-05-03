<?php
require_once __DIR__ . '/../config/database.php';

class EkupiGateway
{
    public static function ensureSchema(PDO $db): void
    {
        // Table for tracking payment orders
        $db->exec("
            CREATE TABLE IF NOT EXISTS payment_orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                client_txn_id VARCHAR(100) NOT NULL UNIQUE,
                gateway_order_id VARCHAR(100) NULL,
                amount DECIMAL(10,2) NOT NULL,
                status ENUM('pending','created','success','failed') DEFAULT 'pending',
                p_info VARCHAR(255) NULL,
                customer_name VARCHAR(100) NULL,
                customer_email VARCHAR(100) NULL,
                customer_mobile VARCHAR(20) NULL,
                redirect_url VARCHAR(1000) NULL,
                udf1 VARCHAR(255) NULL,
                udf2 VARCHAR(255) NULL,
                udf3 VARCHAR(255) NULL,
                utr VARCHAR(100) NULL,
                remarks VARCHAR(255) NULL,
                gateway_response TEXT NULL,
                last_checked_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX (status),
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ");

        // Ensure settings table exists (usually handled by init.sql but for safety)
        $db->exec("
            CREATE TABLE IF NOT EXISTS settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) NOT NULL UNIQUE,
                setting_value TEXT
            )
        ");
    }

    public static function getSettings(PDO $db): array
    {
        $keys = [
            'ekupi_key',
            'ekupi_base_url',
            'ekupi_redirect_url',
            'ekupi_webhook_token'
        ];

        $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        foreach ($stmt->fetchAll() as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        return [
            'key' => trim($settings['ekupi_key'] ?? ''),
            'base_url' => rtrim(trim($settings['ekupi_base_url'] ?? 'https://ekupi.in'), '/'),
            'redirect_url' => trim($settings['ekupi_redirect_url'] ?? ''),
            'webhook_token' => trim($settings['ekupi_webhook_token'] ?? '')
        ];
    }

    public static function createOrder(array $settings, array $payload): array
    {
        $url = $settings['base_url'] . '/api/v1/order/create-order';
        $payload['key'] = $settings['key']; // Add the API key to the body
        return self::postJson($url, $settings['key'], $payload);
    }

    public static function checkOrderStatus(array $settings, string $clientTxnId): array
    {
        $url = $settings['base_url'] . '/api/v1/order/check-order-status';
        return self::postJson($url, $settings['key'], [
            'key' => $settings['key'],
            'client_txn_id' => $clientTxnId
        ]);
    }

    public static function finalizeSuccess(PDO $db, string $clientTxnId, ?string $utr, string $rawPayload): bool
    {
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("SELECT * FROM payment_orders WHERE client_txn_id = ? FOR UPDATE");
            $stmt->execute([$clientTxnId]);
            $order = $stmt->fetch();

            if (!$order) {
                $db->rollBack();
                return false;
            }

            // Already processed
            if ($order['status'] === 'success') {
                $db->commit();
                return true;
            }

            // 1. Credit the user balance
            $stmt = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $stmt->execute([$order['amount'], $order['user_id']]);

            // 2. Record the transaction
            $description = 'Funds added via eKupi. Txn: ' . $clientTxnId;
            if ($utr) $description .= ' (UTR: ' . $utr . ')';
            
            $stmt = $db->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'credit', ?)");
            $stmt->execute([$order['user_id'], $order['amount'], $description]);

            // 3. Update the payment order status
            $stmt = $db->prepare("
                UPDATE payment_orders
                SET status = 'success', utr = ?, gateway_response = ?, last_checked_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$utr, $rawPayload, $order['id']]);

            $db->commit();
            return true;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Ekupi Finalize Error: " . $e->getMessage());
            return false;
        }
    }

    public static function markFailed(PDO $db, string $clientTxnId, string $rawPayload): void
    {
        $stmt = $db->prepare("
            UPDATE payment_orders
            SET status = 'failed', gateway_response = ?, last_checked_at = NOW()
            WHERE client_txn_id = ? AND status <> 'success'
        ");
        $stmt->execute([$rawPayload, $clientTxnId]);
    }

    private static function postJson(string $url, string $key, array $body): array
    {
        if ($key === '') {
            return ['ok' => false, 'error' => 'eKupi API key is not configured in settings.'];
        }

        $ch = curl_init($url);
        $json = json_encode($body);
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $key,
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true // Ensure secure connection
        ]);

        $response = curl_exec($ch);
        $errNo = curl_errno($ch);
        $err = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errNo !== 0) {
            return ['ok' => false, 'error' => 'Connection failed: ' . $err];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return [
                'ok' => false, 
                'error' => 'Invalid response from gateway (not JSON).', 
                'http_code' => $httpCode, 
                'raw' => substr($response, 0, 500)
            ];
        }

        $isSuccess = !empty($decoded['success']);
        if (!$isSuccess) {
            return [
                'ok' => false,
                'error' => $decoded['msg'] ?? 'Gateway returned an error.',
                'http_code' => $httpCode,
                'data' => $decoded
            ];
        }

        return ['ok' => true, 'http_code' => $httpCode, 'data' => $decoded];
    }
}
