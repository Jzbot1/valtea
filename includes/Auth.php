<?php
// includes/Auth.php
require_once __DIR__ . '/../config/database.php';

class Auth {
    public static function startSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function getGoogleLoginUrl($clientId, $redirectUri) {
        $params = [
            'response_type' => 'code',
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            'scope'         => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
            'access_type'   => 'online',
            'prompt'        => 'select_account'
        ];
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    public static function handleGoogleCallback($code, $clientId, $clientSecret, $redirectUri) {
        $db = Database::getInstance();
        
        // Exchange code for token
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'code'          => $code,
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri'  => $redirectUri,
            'grant_type'    => 'authorization_code'
        ]);
        $response = curl_exec($ch);
        $data = json_decode($response, true);
        
        if (!isset($data['access_token'])) return false;
        
        // Get User Info
        $ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $data['access_token']]);
        $user_res = curl_exec($ch);
        $google_user = json_decode($user_res, true);
        
        if (!isset($google_user['email'])) return false;
        
        // Find or Create User
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$google_user['email']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $stmt = $db->prepare("INSERT INTO users (name, email, password, role, api_key) VALUES (?, ?, ?, 'user', ?)");
            $stmt->execute([
                $google_user['name'] ?? 'Google User',
                $google_user['email'],
                password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                bin2hex(random_bytes(16))
            ]);
            $user_id = $db->lastInsertId();
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        }
        
        self::startSession();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        return true;
    }

    public static function checkLogin() {
        self::startSession();
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "/login");
            exit;
        }
    }

    public static function checkAdmin() {
        self::startSession();
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header("Location: " . BASE_URL . "/login");
            exit;
        }
    }

    public static function login($email, $password) {
        self::startSession();
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            return true;
        }
        return false;
    }

    public static function logout() {
        self::startSession();
        session_destroy();
        header("Location: " . BASE_URL . "/login");
        exit;
    }

    public static function generateApiKey() {
        return bin2hex(random_bytes(16));
    }
}
