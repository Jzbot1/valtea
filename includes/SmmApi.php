<?php
// includes/SmmApi.php

class SmmApi {
    public $api_url = ''; 
    public $api_key = ''; 

    public function __construct() {
        // Fetch from DB settings
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'smm_api_url'");
        $stmt->execute();
        $this->api_url = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'smm_api_key'");
        $stmt->execute();
        $this->api_key = $stmt->fetchColumn();
    }

    public function order($data) { 
        $post = array_merge(['key' => $this->api_key, 'action' => 'add'], $data);
        return json_decode($this->connect($post));
    }

    public function status($order_id) { 
        return json_decode($this->connect([
            'key' => $this->api_key,
            'action' => 'status',
            'order' => $order_id
        ]));
    }

    public function multiStatus($order_ids) { 
        return json_decode($this->connect([
            'key' => $this->api_key,
            'action' => 'status',
            'orders' => implode(",", (array)$order_ids)
        ]));
    }

    public function services() { 
        return json_decode($this->connect([
            'key' => $this->api_key,
            'action' => 'services',
        ]));
    }

    public function balance() { 
        return json_decode($this->connect([
            'key' => $this->api_key,
            'action' => 'balance',
        ]));
    }

    public function refill($order_id) {
        return json_decode($this->connect([
            'key' => $this->api_key,
            'action' => 'refill',
            'order' => $order_id
        ]));
    }

    private function connect($post) {
        $ch = curl_init($this->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 seconds total timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // 5 seconds connection timeout
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            return json_encode(['error' => $error_msg]);
        }
        curl_close($ch);
        return $result;
    }
}
