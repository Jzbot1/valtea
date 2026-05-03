<?php
require_once 'includes/header.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_balance'])) {
        $user_id = $_POST['user_id'];
        $amount = (float)$_POST['amount'];
        
        $db->beginTransaction();
        
        $stmt = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$amount, $user_id]);

        $stmt = $db->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'credit', ?)");
        $stmt->execute([$user_id, $amount, "Admin added funds"]);

        $db->commit();
        $message = "Funds added successfully!";
    } elseif (isset($_POST['update_role'])) {
        $user_id = $_POST['user_id'];
        $role = $_POST['role'];
        $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$role, $user_id]);
        $message = "Role updated!";
    }
}

$stmt = $db->query("SELECT * FROM users ORDER BY id DESC");
$users = $stmt->fetchAll();
?>

<div class="glass rounded-2xl p-6 md:p-8 shadow-2xl">
    <h2 class="text-2xl font-bold text-white mb-6">Manage Users</h2>

    <?php if ($message): ?>
        <div class="p-4 rounded-lg mb-6 bg-green-500/10 border border-green-500/50 text-green-400">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-slate-400 text-sm border-b border-slate-700/50">
                    <th class="pb-3 pr-4 font-medium">ID</th>
                    <th class="pb-3 pr-4 font-medium">Name</th>
                    <th class="pb-3 pr-4 font-medium">Email</th>
                    <th class="pb-3 pr-4 font-medium">Balance</th>
                    <th class="pb-3 pr-4 font-medium">Role</th>
                    <th class="pb-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                <?php foreach ($users as $u): ?>
                    <tr class="border-b border-slate-700/30 hover:bg-slate-800/30 transition-colors">
                        <td class="py-3 pr-4 text-slate-400"><?php echo htmlspecialchars($u['id']); ?></td>
                        <td class="py-3 pr-4 text-white font-medium"><?php echo htmlspecialchars($u['name']); ?></td>
                        <td class="py-3 pr-4 text-slate-300"><?php echo htmlspecialchars($u['email']); ?></td>
                        <td class="py-3 pr-4 text-green-400 font-semibold">₹<?php echo number_format($u['balance'], 2); ?></td>
                        <td class="py-3 pr-4 text-slate-300">
                            <form method="POST" action="" class="flex items-center space-x-2">
                                <input type="hidden" name="update_role" value="1">
                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                <select name="role" class="bg-slate-800 border border-slate-700 rounded px-2 py-1 text-xs" onchange="this.form.submit()">
                                    <option value="user" <?php echo $u['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                    <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </form>
                        </td>
                        <td class="py-3">
                            <form method="POST" action="" class="flex items-center space-x-2" onsubmit="return confirm('Add funds to this user?');">
                                <input type="hidden" name="add_balance" value="1">
                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                <input type="number" step="0.01" name="amount" placeholder="Amount" required class="w-20 bg-slate-800 border border-slate-700 rounded px-2 py-1 text-xs">
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-2 py-1 rounded text-xs">Add</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
