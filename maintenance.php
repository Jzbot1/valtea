<?php
$skip_auth = true;
require_once 'config/database.php';

$db = Database::getInstance();
$stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'site_name'");
$site_name = $stmt->fetchColumn() ?: 'SMM Panel';
$stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'support_number'");
$support_number = $stmt->fetchColumn() ?: '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode - <?php echo htmlspecialchars($site_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #0f172a; color: #f8fafc; }
        .glass { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6 bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-indigo-900/20 via-slate-950 to-slate-950">
    
    <div class="max-w-md w-full text-center">
        <div class="mb-10 relative">
            <div class="w-24 h-24 bg-indigo-600/20 rounded-full mx-auto flex items-center justify-center animate-pulse">
                <i class="fas fa-tools text-indigo-400 text-4xl"></i>
            </div>
            <div class="absolute -top-2 -right-2 bg-red-500 text-white text-[10px] font-bold px-2 py-1 rounded-full uppercase tracking-tighter">Offline</div>
        </div>

        <h1 class="text-4xl font-black text-white mb-4">Under Maintenance</h1>
        <p class="text-slate-400 mb-10 leading-relaxed">
            We're currently performing some scheduled upgrades to improve your experience. 
            We'll be back online very shortly!
        </p>

        <div class="glass rounded-3xl p-6 mb-8">
            <h3 class="text-sm font-bold text-slate-300 uppercase tracking-widest mb-4">Need Help?</h3>
            <div class="flex items-center justify-center space-x-4">
                <?php if ($support_number): ?>
                    <a href="https://wa.me/<?php echo preg_replace('/\D+/', '', $support_number); ?>" class="flex items-center space-x-2 text-green-400 hover:text-green-300 transition-colors">
                        <i class="fab fa-whatsapp text-xl"></i>
                        <span class="text-sm font-semibold">WhatsApp Support</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-slate-500 text-xs">
            &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_name); ?>. All rights reserved.
            <br>
            <a href="login" class="mt-4 inline-block hover:text-indigo-400">Admin Login</a>
        </div>
    </div>

</body>
</html>
