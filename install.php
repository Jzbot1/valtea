<?php
// install.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 1) {
        $db_host = $_POST['db_host'] ?? 'localhost';
        $db_name = $_POST['db_name'] ?? '';
        $db_user = $_POST['db_user'] ?? '';
        $db_pass = $_POST['db_pass'] ?? '';

        try {
            // Try to connect to MySQL
            $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$db_name`;");

            // Update database.php file
            $config_content = "<?php
// config/database.php

define('DB_HOST', '$db_host');
define('DB_NAME', '$db_name');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');

class Database {
    private static \$instance = null;
    private \$conn;

    private function __construct() {
        try {
            \$this->conn = new PDO(
                \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=utf8mb4\",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException \$e) {
            die(\"Database connection failed: \" . \$e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::\$instance == null) {
            self::\$instance = new Database();
        }
        return self::\$instance->conn;
    }
}
";
            if (!file_put_contents('config/database.php', $config_content)) {
                $error = "Failed to write config file. Check permissions on config/database.php";
            } else {
                // Import tables one by one for better compatibility
                $sql = file_get_contents('init.sql');
                if ($sql) {
                    $statements = array_filter(array_map('trim', explode(';', $sql)));
                    foreach ($statements as $stmt_sql) {
                        try {
                            $pdo->exec($stmt_sql);
                        } catch (PDOException $e) {
                            // If it's a "Table already exists" error (1050), we can ignore it
                            if ($e->getCode() !== '42S01') {
                                throw $e;
                            }
                        }
                    }
                    header("Location: install?step=2");
                    exit;
                } else {
                    $error = "init.sql file not found.";
                }
            }
        } catch (PDOException $e) {
            $error = "Connection failed: " . $e->getMessage();
        }
    } elseif ($step === 2) {
        require_once 'config/database.php';
        $admin_name = $_POST['admin_name'] ?? '';
        $admin_email = $_POST['admin_email'] ?? '';
        $admin_pass = $_POST['admin_pass'] ?? '';

        if ($admin_name && $admin_email && $admin_pass) {
            $db = Database::getInstance();
            $hashed = password_hash($admin_pass, PASSWORD_DEFAULT);
            $api_key = bin2hex(random_bytes(16));
            
            // Clear existing users if any to avoid duplicates on fresh install
            $db->exec("DELETE FROM users");
            
            $stmt = $db->prepare("INSERT INTO users (name, email, password, api_key, role) VALUES (?, ?, ?, ?, 'admin')");
            if ($stmt->execute([$admin_name, $admin_email, $hashed, $api_key])) {
                $success = "Installation complete! You can now log in.";
                // Rename install.php to prevent re-run
                // rename('install.php', 'install.php.bak'); // Uncomment in production
            } else {
                $error = "Failed to create admin account.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Wizard - SMM Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #0f172a; color: #f8fafc; }
        .glass { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); }
        .btn-primary { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); transition: all 0.3s ease; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 20px -10px #4f46e5; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6 bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-indigo-900/20 via-slate-950 to-slate-950">
    
    <div class="max-w-xl w-full">
        <!-- Header -->
        <div class="text-center mb-10">
            <div class="w-20 h-20 bg-indigo-600 rounded-3xl p-5 shadow-2xl shadow-indigo-600/20 mx-auto mb-6 flex items-center justify-center">
                <i class="fas fa-rocket text-white text-3xl"></i>
            </div>
            <h1 class="text-4xl font-black text-white mb-2">Installation Wizard</h1>
            <p class="text-slate-400">Set up your premium SMM Panel in minutes</p>
        </div>

        <div class="glass rounded-3xl p-8 md:p-10 shadow-2xl relative overflow-hidden">
            <!-- Progress Bar -->
            <div class="flex items-center justify-center space-x-4 mb-10">
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 rounded-full <?php echo $step >= 1 ? 'bg-indigo-600' : 'bg-slate-800'; ?> flex items-center justify-center text-xs font-bold text-white mb-1">1</div>
                    <span class="text-[10px] uppercase font-bold tracking-widest text-slate-500">Database</span>
                </div>
                <div class="w-12 h-[1px] <?php echo $step >= 2 ? 'bg-indigo-600' : 'bg-slate-800'; ?> mb-4"></div>
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 rounded-full <?php echo $step >= 2 ? 'bg-indigo-600' : 'bg-slate-800'; ?> flex items-center justify-center text-xs font-bold text-white mb-1">2</div>
                    <span class="text-[10px] uppercase font-bold tracking-widest text-slate-500">Admin</span>
                </div>
                <div class="w-12 h-[1px] <?php echo $step >= 3 ? 'bg-indigo-600' : 'bg-slate-800'; ?> mb-4"></div>
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 rounded-full <?php echo $success ? 'bg-green-500' : 'bg-slate-800'; ?> flex items-center justify-center text-xs font-bold text-white mb-1">
                        <i class="fas fa-check"></i>
                    </div>
                    <span class="text-[10px] uppercase font-bold tracking-widest text-slate-500">Finish</span>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-500/10 border border-red-500/20 text-red-400 p-4 rounded-2xl mb-8 flex items-start space-x-3 text-sm">
                    <i class="fas fa-exclamation-circle mt-0.5"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="text-center py-6">
                    <div class="w-20 h-20 bg-green-500/10 rounded-full flex items-center justify-center mx-auto mb-6 text-green-500">
                        <i class="fas fa-check-circle text-5xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-white mb-4">Success!</h2>
                    <p class="text-slate-400 mb-8"><?php echo $success; ?></p>
                    <a href="login" class="inline-block btn-primary text-white font-bold py-4 px-10 rounded-2xl">
                        Go to Login
                    </a>
                </div>
            <?php elseif ($step === 1): ?>
                <form method="POST" class="space-y-6">
                    <div class="group">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">DB Host</label>
                        <input type="text" name="db_host" value="localhost" required class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-indigo-500 transition-all">
                    </div>
                    <div class="group">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">DB Name</label>
                        <input type="text" name="db_name" required placeholder="e.g. smm_panel" class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-indigo-500 transition-all">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="group">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">DB User</label>
                            <input type="text" name="db_user" value="root" required class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-indigo-500 transition-all">
                        </div>
                        <div class="group">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">DB Pass</label>
                            <input type="password" name="db_pass" placeholder="Password" class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-indigo-500 transition-all">
                        </div>
                    </div>
                    <button type="submit" class="w-full btn-primary text-white font-bold py-5 rounded-2xl shadow-xl mt-4">
                        Test & Continue <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </form>
            <?php elseif ($step === 2): ?>
                <form method="POST" class="space-y-6">
                    <div class="group">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Admin Name</label>
                        <input type="text" name="admin_name" required placeholder="Your Name" class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-indigo-500 transition-all">
                    </div>
                    <div class="group">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Admin Email</label>
                        <input type="email" name="admin_email" required placeholder="admin@example.com" class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-indigo-500 transition-all">
                    </div>
                    <div class="group">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Admin Password</label>
                        <input type="password" name="admin_pass" required placeholder="••••••••" class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-indigo-500 transition-all">
                    </div>
                    <button type="submit" class="w-full btn-primary text-white font-bold py-5 rounded-2xl shadow-xl mt-4">
                        Complete Setup <i class="fas fa-check ml-2"></i>
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <p class="text-center text-slate-600 text-xs mt-8">
            &copy; <?php echo date('Y'); ?> Premium SMM Panel. All rights reserved.
        </p>
    </div>
</body>
</html>
