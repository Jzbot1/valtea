<?php
// login.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'includes/Auth.php';

// Check if constants are defined
if (!defined('BASE_URL')) {
    die("Error: BASE_URL is not defined. Please ensure config/config.php is present and included in config/database.php.");
}

Auth::startSession();
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: " . BASE_URL . "/admin/index");
    } else {
        header("Location: " . BASE_URL . "/index");
    }
    exit;
}

$error = '';

// Check if installed
if (!defined('DB_NAME') || DB_NAME === '' || DB_NAME === 'smm_panel') {
    // Try a test connection, if fails, redirect to install
    try {
        $db = Database::getInstance();
    } catch (Exception $e) {
        header("Location: " . BASE_URL . "/install");
        exit;
    }
} else {
    $db = Database::getInstance();
}

// Fetch Site Name
$stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'site_name'");
$site_name = $stmt->fetchColumn() ?: 'SMM Panel';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (Auth::login($email, $password)) {
        if ($_SESSION['role'] === 'admin') {
            header("Location: " . BASE_URL . "/admin/index");
        } else {
            header("Location: " . BASE_URL . "/index");
        }
        exit;
    } else {
        $error = 'Invalid email or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars($site_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
        }

        .glass {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4">
    <div class="glass p-8 rounded-2xl w-full max-w-md shadow-2xl">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-400 to-indigo-500 bg-clip-text text-transparent">
                <?php echo htmlspecialchars($site_name); ?></h1>
            <p class="text-slate-400 mt-2">Sign in to your account</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/50 text-red-500 p-3 rounded-lg mb-6 text-sm">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-300 mb-2">Email Address</label>
                <input type="email" name="email" required
                    class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-300 mb-2">Password</label>
                <input type="password" name="password" required
                    class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors">
            </div>
            <button type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 rounded-lg transition-colors shadow-lg shadow-indigo-600/30">
                Sign In
            </button>
        </form>

        <?php
        $stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('google_auth_enabled', 'google_client_id')");
        $g_settings = [];
        foreach ($stmt->fetchAll() as $row) {
            $g_settings[$row['setting_key']] = $row['setting_value'];
        }

        if (($g_settings['google_auth_enabled'] ?? '0') === '1' && !empty($g_settings['google_client_id'])):
            $googleLoginUrl = Auth::getGoogleLoginUrl($g_settings['google_client_id'], FULL_URL . '/google_callback');
        ?>
            <div class="relative my-8">
                <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-slate-700"></div></div>
                <div class="relative flex justify-center text-sm"><span class="px-2 bg-[#1e293b] text-slate-500">Or continue with</span></div>
            </div>

            <a href="<?php echo $googleLoginUrl; ?>" class="w-full flex items-center justify-center space-x-3 bg-white hover:bg-slate-100 text-slate-900 font-bold py-3 rounded-lg transition-colors shadow-lg">
                <i class="fab fa-google text-red-500"></i>
                <span>Sign in with Google</span>
            </a>
        <?php endif; ?>
        <p class="mt-6 text-center text-sm text-slate-400">
            Don't have an account? <a href="<?php echo BASE_URL; ?>/register" class="text-indigo-400 hover:text-indigo-300">Sign up</a>
        </p>
    </div>
</body>

</html>