<?php
require_once __DIR__ . '/../config/database.php';

class JzstoreGateway
{
    public static function getSettings(PDO $db): array
    {
        $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        foreach ($stmt->fetchAll() as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        return [
            'token' => trim($settings['jzstore_token'] ?? ''),
            'base_url' => rtrim(trim($settings['jzstore_base_url'] ?? 'https://cash.free.jzstore.in'), '/'),
            'redirect_url' => trim($settings['jzstore_redirect_url'] ?? '')
        ];
    }

    public static function createOrder(array $settings, array $payload): array
    {
        $url = $settings['base_url'] . '/api/create-order';
        
        // Map payload to JZStore format
        $data = [
            'user_token' => $settings['token'],
            'customer_mobile' => $payload['customer_mobile'],
            'amount' => $payload['amount'],
            'order_id' => $payload['client_txn_id'], // We use our client_txn_id as their order_id
            'redirect_url' => $payload['redirect_url'],
            'remark1' => $payload['p_info'] ?? 'Wallet Add Fund',
            'remark2' => 'uid:' . ($payload['user_id'] ?? '')
        ];

        return self::postForm($url, $data);
    }

    public static function checkOrderStatus(array $settings, string $clientTxnId): array
    {
        $url = $settings['base_url'] . '/api/check-order-status';
        return self::postForm($url, [
            'user_token' => $settings['token'],
            'order_id' => $clientTxnId
        ]);
    }

    private static function postForm(string $url, array $body): array
    {
        if (empty($body['user_token'])) {
            return ['ok' => false, 'error' => 'JZStore User Token is not configured.'];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($body),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
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

        // JZStore uses "status": true/false
        $isSuccess = isset($decoded['status']) && $decoded['status'] === true;
        
        if (!$isSuccess) {
            return [
                'ok' => false,
                'error' => $decoded['message'] ?? 'Gateway returned an error.',
                'http_code' => $httpCode,
                'data' => $decoded
            ];
        }

        return ['ok' => true, 'http_code' => $httpCode, 'data' => $decoded];
    }
}
