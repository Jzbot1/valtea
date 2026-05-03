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

<div class="max-w-7xl mx-auto">
    <div class="mb-6 text-center md:text-left">
        <h2 class="text-2xl font-black text-white tracking-tight">User Management</h2>
        <p class="text-slate-400 text-sm mt-1">Manage user accounts and balances</p>
    </div>

    <?php if ($message): ?>
        <div class="p-4 rounded-xl mb-6 flex items-start space-x-3 bg-green-500/10 border border-green-500/30 text-green-400 text-sm">
            <i class="fas fa-check-circle mt-0.5"></i>
            <div><?php echo htmlspecialchars($message); ?></div>
        </div>
    <?php endif; ?>

    <div class="glass-card overflow-hidden shadow-2xl">
        <!-- Desktop View -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-white/5 bg-white/5">
                        <th class="px-6 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest">ID</th>
                        <th class="px-6 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest">User Info</th>
                        <th class="px-6 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest">Balance</th>
                        <th class="px-6 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest">Role</th>
                        <th class="px-6 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="px-6 py-4 text-xs font-bold text-slate-500">#<?php echo $u['id']; ?></td>
                            <td class="px-6 py-4">
                                <div class="text-xs font-bold text-white"><?php echo htmlspecialchars($u['name']); ?></div>
                                <div class="text-[10px] text-slate-500 mt-0.5"><?php echo htmlspecialchars($u['email']); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs font-black text-green-400">₹<?php echo number_format($u['balance'], 2); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <form method="POST" action="">
                                    <input type="hidden" name="update_role" value="1">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <select name="role" class="bg-transparent !border-none !p-0 text-[10px] font-black uppercase tracking-widest text-cyan-400 cursor-pointer focus:ring-0" onchange="this.form.submit()">
                                        <option value="user" class="bg-slate-900" <?php echo $u['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                        <option value="admin" class="bg-slate-900" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </form>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <form method="POST" action="" class="flex items-center justify-end space-x-2">
                                    <input type="hidden" name="add_balance" value="1">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <input type="number" step="0.01" name="amount" placeholder="Amt" required class="w-20 !py-1 !px-2 !text-[10px] !rounded-lg">
                                    <button type="submit" class="bg-cyan-400/10 hover:bg-cyan-400 text-cyan-400 hover:text-white border border-cyan-400/20 px-3 py-1 rounded-lg text-[10px] font-black uppercase transition-all">Add</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile View -->
        <div class="md:hidden divide-y divide-white/5">
            <?php foreach ($users as $u): ?>
                <div class="p-4 space-y-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-[10px] font-bold text-slate-500">#<?php echo $u['id']; ?></span>
                            <h4 class="text-white font-bold text-sm"><?php echo htmlspecialchars($u['name']); ?></h4>
                            <p class="text-[10px] text-slate-500"><?php echo htmlspecialchars($u['email']); ?></p>
                        </div>
                        <div class="text-right">
                            <span class="text-[9px] text-slate-500 uppercase font-black block mb-1">Balance</span>
                            <span class="text-xs font-black text-green-400">₹<?php echo number_format($u['balance'], 2); ?></span>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between pt-3 border-t border-white/5 gap-3">
                        <form method="POST" action="">
                            <input type="hidden" name="update_role" value="1">
                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                            <select name="role" class="bg-slate-900/50 border border-white/10 rounded-lg px-3 py-2 text-[10px] font-black uppercase tracking-widest text-cyan-400" onchange="this.form.submit()">
                                <option value="user" <?php echo $u['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </form>

                        <form method="POST" action="" class="flex items-center flex-1 space-x-2">
                            <input type="hidden" name="add_balance" value="1">
                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                            <input type="number" step="0.01" name="amount" placeholder="Amount" required class="flex-1 !py-2 !text-[10px]">
                            <button type="submit" class="bg-cyan-400 text-white px-3 py-2 rounded-lg text-[10px] font-black uppercase">Add</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
