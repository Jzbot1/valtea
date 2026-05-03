<?php
// register.php
require_once 'config/database.php';
require_once 'includes/Auth.php';

Auth::startSession();
if (isset($_SESSION['user_id'])) {
    header("Location: /smm/index");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!$name || !$email || !$password) {
        $error = 'All fields are required';
    } else {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already exists';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $api_key = Auth::generateApiKey();
            
            $stmt = $db->prepare("INSERT INTO users (name, email, password, api_key, role) VALUES (?, ?, ?, ?, 'user')");
            if ($stmt->execute([$name, $email, $hashed, $api_key])) {
                Auth::login($email, $password);
                header("Location: " . BASE_URL . "/index");
                exit;
            } else {
                $error = 'Registration failed';
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
    <title>Register - SMM Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; color: #f8fafc; }
        .glass { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="glass p-8 rounded-2xl w-full max-w-md shadow-2xl">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-400 to-indigo-500 bg-clip-text text-transparent">Create Account</h1>
            <p class="text-slate-400 mt-2">Join our SMM Panel today</p>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/50 text-red-500 p-3 rounded-lg mb-6 text-sm">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-300 mb-2">Full Name</label>
                <input type="text" name="name" required class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-300 mb-2">Email Address</label>
                <input type="email" name="email" required class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-300 mb-2">Password</label>
                <input type="password" name="password" required class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors">
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 rounded-lg transition-colors shadow-lg shadow-indigo-600/30">
                Sign Up
            </button>
        </form>
        <p class="mt-6 text-center text-sm text-slate-400">
            Already have an account? <a href="<?php echo BASE_URL; ?>/login" class="text-indigo-400 hover:text-indigo-300">Sign in</a>
        </p>
    </div>
</body>
</html>
