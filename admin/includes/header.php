<?php
// admin/includes/header.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/Auth.php';

Auth::checkAdmin();

$db = Database::getInstance();
// Fetch site name
$stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'site_name'");
$site_name = $stmt->fetchColumn() ?: 'SMM Panel';
$stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'site_favicon'");
$site_favicon = $stmt->fetchColumn() ?: '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?php echo htmlspecialchars($site_name); ?></title>
    <?php if ($site_favicon): ?>
        <link rel="icon" href="<?php echo htmlspecialchars($site_favicon); ?>" type="image/x-icon">
    <?php endif; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; color: #f8fafc; }
        .glass { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.05); }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    <aside class="w-64 glass border-r border-slate-700/50 flex-shrink-0 hidden md:flex flex-col">
        <div class="h-16 flex items-center px-6 border-b border-slate-700/50">
            <h1 class="text-xl font-bold text-red-400"><?php echo htmlspecialchars($site_name); ?></h1>
        </div>
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
            <a href="<?php echo BASE_URL; ?>/admin/index" class="flex items-center px-3 py-2 text-slate-300 rounded-lg hover:bg-slate-800 transition-colors">
                <i class="fas fa-tachometer-alt w-6"></i> Dashboard
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/products" class="flex items-center px-3 py-2 text-slate-300 rounded-lg hover:bg-slate-800 transition-colors">
                <i class="fas fa-th-list w-6"></i> Services
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/sync" class="flex items-center px-3 py-2 text-slate-300 rounded-lg hover:bg-slate-800 transition-colors">
                <i class="fas fa-sync w-6"></i> Sync Services
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/orders" class="flex items-center px-3 py-2 text-slate-300 rounded-lg hover:bg-slate-800 transition-colors">
                <i class="fas fa-shopping-cart w-6"></i> Orders
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/users" class="flex items-center px-3 py-2 text-slate-300 rounded-lg hover:bg-slate-800 transition-colors">
                <i class="fas fa-users w-6"></i> Users
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/settings" class="flex items-center px-3 py-2 text-slate-300 rounded-lg hover:bg-slate-800 transition-colors">
                <i class="fas fa-cog w-6"></i> Settings
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/notifications" class="flex items-center px-3 py-2 text-slate-300 rounded-lg hover:bg-slate-800 transition-colors">
                <i class="fas fa-bell w-6"></i> Notifications
            </a>
            <div class="pt-4 mt-4 border-t border-slate-700/50">
                <a href="<?php echo BASE_URL; ?>/index" class="flex items-center px-3 py-2 text-indigo-400 rounded-lg hover:bg-slate-800 transition-colors">
                    <i class="fas fa-external-link-alt w-6"></i> User Panel
                </a>
            </div>
        </nav>
    </aside>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <header class="h-16 glass border-b border-slate-700/50 flex items-center justify-between px-6 z-10">
            <div class="flex items-center md:hidden">
                <h1 class="text-xl font-bold text-red-400">Admin</h1>
            </div>
        </header>
        <main class="flex-1 overflow-y-auto p-6 relative">
