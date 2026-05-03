<?php
// includes/header.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Auth.php';

Auth::checkLogin();

$db = Database::getInstance();

// Fetch current user data
try {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch (Exception $e) {
    header("Location: " . BASE_URL . "/install");
    exit;
}

if (!$user) {
    session_destroy();
    header("Location: " . BASE_URL . "/login");
    exit;
}
// Fetch site settings
$stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('site_name', 'site_logo', 'site_favicon', 'support_number', 'facebook_url', 'twitter_url', 'instagram_url', 'telegram_url', 'maintenance_mode')");
$site_settings = [];
foreach ($stmt->fetchAll() as $row) {
    $site_settings[$row['setting_key']] = $row['setting_value'];
}

// Maintenance Mode Check
if (($site_settings['maintenance_mode'] ?? '0') === '1' && ($user['role'] ?? '') !== 'admin') {
    // Only allow if NOT on maintenance.php itself to avoid loops
    if (basename($_SERVER['PHP_SELF']) !== 'maintenance.php' && basename($_SERVER['PHP_SELF']) !== 'login.php') {
        header("Location: " . BASE_URL . "/maintenance");
        exit;
    }
}

$site_name = $site_settings['site_name'] ?? 'SMM Panel';
$site_logo = $site_settings['site_logo'] ?? '';
$site_favicon = $site_settings['site_favicon'] ?? '';
$support_number = $site_settings['support_number'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site_name); ?> - Premium SMM Services</title>
    <meta name="description" content="Premium SMM Panel - Social Media Marketing Services">
    <?php if ($site_favicon): ?>
        <link rel="icon" href="<?php echo htmlspecialchars($site_favicon); ?>" type="image/x-icon">
    <?php endif; ?>
    <link rel="manifest" href="<?php echo BASE_URL; ?>/manifest.json">
    <meta name="theme-color" content="#4f46e5">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --bg-dark: #0f172a;
            --glass-bg: rgba(30, 41, 59, 0.7);
            --glass-border: rgba(255, 255, 255, 0.08);
        }
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--bg-dark); 
            color: #f8fafc;
            background-image: radial-gradient(circle at 50% -20%, #1e1b4b 0%, #0f172a 100%);
            background-attachment: fixed;
        }
        .glass { 
            background: var(--glass-bg); 
            backdrop-filter: blur(12px); 
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border); 
        }
        .glass-card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(8px);
            border: 1px solid var(--glass-border);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .glass-card:hover {
            border-color: rgba(99, 102, 241, 0.3);
            transform: translateY(-2px);
        }
        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4);
            transform: translateY(-1px);
        }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }
        
        .nav-link-active {
            color: white !important;
            background: rgba(99, 102, 241, 0.15) !important;
            border: 1px solid rgba(99, 102, 241, 0.2) !important;
        }
    </style>
</head>
<body class="min-h-screen pb-20 md:pb-0">

    <!-- Top Navigation -->
    <nav class="glass border-b border-slate-700/50 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 md:h-20">
                <a href="<?php echo BASE_URL; ?>/index" class="flex items-center space-x-3 group">
                    <div class="bg-indigo-600 rounded-xl p-1.5 shadow-lg shadow-indigo-600/20 group-hover:scale-110 transition-transform">
                        <i class="fas fa-bolt text-white text-lg"></i>
                    </div>
                    <span class="text-xl font-black tracking-tight bg-gradient-to-r from-white to-slate-400 bg-clip-text text-transparent uppercase">
                        <?php echo htmlspecialchars($site_name); ?>
                    </span>
                </a>
                
                <div class="hidden md:flex items-center space-x-1">
                    <a href="<?php echo BASE_URL; ?>/index" class="px-4 py-2 text-sm font-medium text-slate-300 hover:text-white rounded-xl hover:bg-slate-800/50 transition-all">
                        New Order
                    </a>
                    <a href="<?php echo BASE_URL; ?>/orders" class="px-4 py-2 text-sm font-medium text-slate-300 hover:text-white rounded-xl hover:bg-slate-800/50 transition-all">
                        Orders
                    </a>
                    <a href="<?php echo BASE_URL; ?>/services" class="px-4 py-2 text-sm font-medium text-slate-300 hover:text-white rounded-xl hover:bg-slate-800/50 transition-all">
                        Services
                    </a>
                    <a href="<?php echo BASE_URL; ?>/add_funds" class="px-4 py-2 text-sm font-medium text-slate-300 hover:text-white rounded-xl hover:bg-slate-800/50 transition-all">
                        Add Funds
                    </a>
                </div>

                <div class="flex items-center space-x-4">
                    <div class="hidden sm:flex flex-col items-end">
                        <span class="text-[10px] text-slate-500 uppercase font-bold tracking-widest">Balance</span>
                        <span class="text-sm font-bold text-indigo-400">₹<?php echo number_format($user['balance'], 2); ?></span>
                    </div>
                    <div class="h-8 w-[1px] bg-slate-700 mx-2 hidden sm:block"></div>
                    <div class="flex items-center space-x-2">
                        <?php if ($user['role'] === 'admin'): ?>
                            <a href="<?php echo BASE_URL; ?>/admin/index" class="p-2 text-slate-400 hover:text-red-400 transition-colors">
                                <i class="fas fa-user-shield text-lg"></i>
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo BASE_URL; ?>/logout" class="p-2 text-slate-400 hover:text-red-400 transition-colors">
                            <i class="fas fa-power-off text-lg"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex-1">
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            
            <?php
            // Announcement Banner
            $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'announcement_enabled'");
            $ann_enabled = $stmt->fetchColumn();
            if ($ann_enabled === '1'):
                $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'announcement_text'");
                $ann_text = $stmt->fetchColumn();
            ?>
                <div class="mb-8 relative overflow-hidden group">
                    <div class="absolute inset-0 bg-gradient-to-r from-indigo-600/20 via-blue-600/20 to-indigo-600/20 animate-gradient-x"></div>
                    <div class="glass border-indigo-500/30 rounded-2xl p-4 flex items-center justify-between relative z-10">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-indigo-600/20">
                                <i class="fas fa-bullhorn animate-bounce"></i>
                            </div>
                            <p class="text-sm font-medium text-slate-200"><?php echo htmlspecialchars($ann_text); ?></p>
                        </div>
                        <button onclick="this.parentElement.parentElement.remove()" class="text-slate-500 hover:text-white transition-colors">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>

    <?php if ($support_number): ?>
        <a href="https://wa.me/<?php echo preg_replace('/\D+/', '', $support_number); ?>" target="_blank" class="fixed bottom-6 right-6 z-[100] bg-green-500 hover:bg-green-600 text-white w-14 h-14 rounded-full flex items-center justify-center shadow-2xl transition-all hover:scale-110 active:scale-95 group">
            <i class="fab fa-whatsapp text-3xl"></i>
            <span class="absolute right-full mr-3 bg-slate-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">Support</span>
        </a>
    <?php endif; ?>