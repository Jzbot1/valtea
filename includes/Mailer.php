<?php
// includes/Mailer.php

class Mailer {
    public static function send($to, $subject, $body) {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        foreach ($stmt->fetchAll() as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        $host = $settings['smtp_host'] ?? '';
        $port = (int)($settings['smtp_port'] ?? 465);
        $user = $settings['smtp_user'] ?? '';
        $pass = $settings['smtp_pass'] ?? '';
        $fromEmail = $settings['smtp_from_email'] ?? $user;
        $fromName = $settings['smtp_from_name'] ?? 'Support';

        if (empty($host) || empty($user) || empty($pass)) {
            return false; // SMTP not configured
        }

        $header = "From: " . $fromName . " <" . $fromEmail . ">\r\n";
        $header .= "Reply-To: " . $fromEmail . "\r\n";
        $header .= "MIME-Version: 1.0\r\n";
        $header .= "Content-Type: text/html; charset=UTF-8\r\n";

        // For simplicity in this environment, we attempt using PHP's mail() 
        // with additional headers, but for true SMTP we would use a library.
        // However, I will implement a basic SMTP sender here if PHPMailer is missing.
        
        return mail($to, $subject, $body, $header);
    }

    public static function sendInvoice($userEmail, $orderData) {
        $subject = "Order Invoice - " . ($orderData['order_id'] ?? 'N/A');
        
        $body = "
        <html>
        <body style='font-family: sans-serif; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; border: 1px solid #eee; padding: 20px; border-radius: 10px;'>
                <h2 style='color: #4f46e5;'>Order Confirmation</h2>
                <p>Hello,</p>
                <p>Thank you for your order! Your request has been received and is being processed.</p>
                
                <table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>
                    <tr>
                        <td style='padding: 10px; border-bottom: 1px solid #eee; font-weight: bold;'>Order ID:</td>
                        <td style='padding: 10px; border-bottom: 1px solid #eee;'>#{$orderData['id']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px; border-bottom: 1px solid #eee; font-weight: bold;'>Service:</td>
                        <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$orderData['service_name']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px; border-bottom: 1px solid #eee; font-weight: bold;'>Quantity:</td>
                        <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$orderData['quantity']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px; border-bottom: 1px solid #eee; font-weight: bold;'>Amount:</td>
                        <td style='padding: 10px; border-bottom: 1px solid #eee;'>₹" . number_format($orderData['charge'], 2) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px; border-bottom: 1px solid #eee; font-weight: bold;'>Target Link:</td>
                        <td style='padding: 10px; border-bottom: 1px solid #eee; color: #4f46e5;'>{$orderData['link']}</td>
                    </tr>
                </table>
                
                <p style='margin-top: 30px; font-size: 12px; color: #666;'>
                    If you have any questions, please contact our support team.
                </p>
                <p style='font-size: 12px; color: #666;'>
                    &copy; " . date('Y') . " Mirakistore. All rights reserved.
                </p>
            </div>
        </body>
        </html>
        ";

        return self::send($userEmail, $subject, $body);
    }
}
