<?php
// config/config.php

// Auto-detect Base URL or set manually
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';
$current_dir = dirname($script_name);

// Handle windows backslashes in some environments
$current_dir = str_replace('\\', '/', $current_dir);

// If we are inside the admin folder, we need to go up one level
$base_path = preg_replace('/\/admin(\/.*)?$/', '', $current_dir);

// Clean up trailing slashes
$base_path = rtrim($base_path, '/');

define('BASE_URL', $base_path);

// Full URL with domain
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('FULL_URL', $protocol . "://" . $host . BASE_URL);
