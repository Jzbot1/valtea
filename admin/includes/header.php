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
        :root {
            --primary-gradient: linear-gradient(153deg, #30cfd0, #330867, hsl(265.2, 76.97859115099217%, 46.58144864334903%));
            --bg-dark: #070b14;
            --glass-bg: rgba(15, 23, 42, 0.6);
            --glass-border: rgba(255, 255, 255, 0.05);
            --accent: #30cfd0;
        }
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--bg-dark); 
            color: #f1f5f9;
            background-image: var(--primary-gradient);
            background-attachment: fixed;
            background-size: cover;
            min-height: 100vh;
        }
        .glass { 
            background: var(--glass-bg); 
            backdrop-filter: blur(16px); 
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border); 
        }
        .glass-card {
            background: rgba(7, 11, 20, 0.4);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1.25rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .glass-card:hover {
            border-color: var(--accent);
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -20px rgba(48, 207, 208, 0.3);
        }
        .btn-primary {
            background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .btn-primary:hover {
            box-shadow: 0 0 20px rgba(48, 207, 208, 0.4);
            transform: scale(1.02);
        }
        .nav-link-active {
            color: var(--accent) !important;
            background: rgba(48, 207, 208, 0.1) !important;
            border: 1px solid rgba(48, 207, 208, 0.2) !important;
        }
        input, select, textarea {
            background: rgba(15, 23, 42, 0.4) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 0.75rem !important;
            font-size: 0.875rem !important;
            padding: 0.75rem 1rem !important;
            transition: all 0.2s ease;
        }
        input:focus, select:focus {
            border-color: var(--accent) !important;
            box-shadow: 0 0 0 4px rgba(48, 207, 208, 0.1) !important;
        }
        /* Mobile optimization */
        @media (max-width: 768px) {
            body { padding-bottom: 80px; }
            .glass-card { padding: 1.25rem !important; }
            h2 { font-size: 1.5rem !important; }
        }
    </style>
</head>
<body class="flex min-h-screen">

    <aside class="w-64 glass border-r border-slate-700/50 flex-shrink-0 hidden md:flex flex-col sticky top-0 h-screen">
        <div class="h-16 flex items-center px-6 border-b border-white/5">
            <h1 class="text-xl font-black text-cyan-400 tracking-tight uppercase"><?php echo htmlspecialchars($site_name); ?></h1>
        </div>
        <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-1">
            <a href="<?php echo BASE_URL; ?>/admin/index" class="flex items-center px-4 py-3 text-[13px] font-semibold text-slate-300 rounded-xl hover:bg-white/5 hover:text-white transition-all">
                <i class="fas fa-tachometer-alt w-6 text-cyan-400"></i> Dashboard
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/products" class="flex items-center px-4 py-3 text-[13px] font-semibold text-slate-300 rounded-xl hover:bg-white/5 hover:text-white transition-all">
                <i class="fas fa-th-list w-6 text-cyan-400"></i> Services
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/orders" class="flex items-center px-4 py-3 text-[13px] font-semibold text-slate-300 rounded-xl hover:bg-white/5 hover:text-white transition-all">
                <i class="fas fa-shopping-cart w-6 text-cyan-400"></i> Orders
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/users" class="flex items-center px-4 py-3 text-[13px] font-semibold text-slate-300 rounded-xl hover:bg-white/5 hover:text-white transition-all">
                <i class="fas fa-users w-6 text-cyan-400"></i> Users
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/settings" class="flex items-center px-4 py-3 text-[13px] font-semibold text-slate-300 rounded-xl hover:bg-white/5 hover:text-white transition-all">
                <i class="fas fa-cog w-6 text-cyan-400"></i> Settings
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/notifications" class="flex items-center px-4 py-3 text-[13px] font-semibold text-slate-300 rounded-xl hover:bg-white/5 hover:text-white transition-all">
                <i class="fas fa-bell w-6 text-cyan-400"></i> Notifications
            </a>
            <div class="pt-4 mt-4 border-t border-white/5">
                <a href="<?php echo BASE_URL; ?>/index" class="flex items-center px-4 py-3 text-[13px] font-semibold text-cyan-400 rounded-xl hover:bg-white/5 transition-all">
                    <i class="fas fa-external-link-alt w-6"></i> User Panel
                </a>
            </div>
        </nav>
    </aside>

    <div class="flex-1 flex flex-col min-h-screen overflow-hidden">
        <header class="h-16 glass border-b border-white/5 flex items-center justify-between px-6 sticky top-0 z-50">
            <div class="flex items-center md:hidden">
                <h1 class="text-xl font-black text-cyan-400 uppercase tracking-tight">Admin</h1>
            </div>
            <div class="flex items-center space-x-4 ml-auto">
                <a href="<?php echo BASE_URL; ?>/logout" class="text-slate-400 hover:text-red-400 transition-colors">
                    <i class="fas fa-power-off"></i>
                </a>
            </div>
        </header>
        <main class="flex-1 p-4 md:p-8 relative overflow-y-auto">
