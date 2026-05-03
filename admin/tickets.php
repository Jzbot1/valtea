<?php
require_once 'includes/header.php';

// Fetch all tickets with user names
$stmt = $db->query("
    SELECT t.*, u.name as user_name 
    FROM tickets t 
    JOIN users u ON t.user_id = u.id 
    ORDER BY t.created_at DESC
");
$tickets = $stmt->fetchAll();
?>

<div class="flex items-center justify-between mb-8">
    <h2 class="text-3xl font-black text-white tracking-tight">Support Management</h2>
</div>

<div class="glass border border-slate-700/50 rounded-3xl overflow-hidden shadow-2xl">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-slate-800/50">
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">ID</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">User</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Subject</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Date</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-700/50">
            <?php foreach ($tickets as $ticket): ?>
                <tr class="hover:bg-slate-800/30 transition-colors">
                    <td class="px-6 py-4 text-sm font-mono text-slate-500">#<?php echo $ticket['id']; ?></td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-white"><?php echo htmlspecialchars($ticket['user_name']); ?></span>
                            <span class="text-[10px] text-slate-500">User ID: #<?php echo $ticket['user_id']; ?></span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm font-bold text-white"><?php echo htmlspecialchars($ticket['subject']); ?></td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-tighter <?php 
                            echo $ticket['status'] === 'Open' ? 'bg-green-500/10 text-green-400 border border-green-500/20' : 
                                ($ticket['status'] === 'Pending' ? 'bg-yellow-500/10 text-yellow-400 border border-yellow-500/20' : 
                                'bg-slate-500/10 text-slate-400 border border-slate-500/20'); 
                        ?>">
                            <?php echo $ticket['status']; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-xs text-slate-400"><?php echo date('M d, Y', strtotime($ticket['created_at'])); ?></td>
                    <td class="px-6 py-4">
                        <a href="view_ticket?id=<?php echo $ticket['id']; ?>" class="bg-slate-700 hover:bg-indigo-600 text-white text-[10px] font-bold px-4 py-2 rounded-lg transition-all active:scale-95 uppercase tracking-widest">
                            Reply
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($tickets)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-20 text-center text-slate-500 italic">No tickets to manage.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>
