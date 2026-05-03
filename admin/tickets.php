<?php
require_once 'includes/header.php';

$stmt = $db->query("
    SELECT t.*, u.name as user_name 
    FROM tickets t 
    JOIN users u ON t.user_id = u.id 
    ORDER BY t.created_at DESC
");
$tickets = $stmt->fetchAll();

$status_classes = [
    'Open' => 'bg-green-500/10 text-green-400 border-green-500/20',
    'Pending' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
    'Closed' => 'bg-slate-500/10 text-slate-500 border-slate-500/20',
    'Answered' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
];
?>

<div class="max-w-7xl mx-auto">
    <div class="mb-6 text-center md:text-left">
        <h2 class="text-2xl font-black text-white tracking-tight">Support Management</h2>
        <p class="text-slate-400 text-sm mt-1">Manage and respond to user inquiries</p>
    </div>

    <div class="glass-card overflow-hidden shadow-2xl">
        <!-- Desktop Table -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-white/5 bg-white/5">
                        <th class="px-6 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest">ID</th>
                        <th class="px-6 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest">User Info</th>
                        <th class="px-6 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest">Subject</th>
                        <th class="px-6 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Status</th>
                        <th class="px-6 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach ($tickets as $ticket): 
                        $cls = $status_classes[$ticket['status']] ?? 'bg-slate-500/10 text-slate-400 border-slate-500/20';
                    ?>
                        <tr class="hover:bg-white/5 transition-colors group">
                            <td class="px-6 py-4 text-xs font-bold text-slate-500">#<?php echo $ticket['id']; ?></td>
                            <td class="px-6 py-4">
                                <div class="text-xs font-bold text-white"><?php echo htmlspecialchars($ticket['user_name']); ?></div>
                                <div class="text-[9px] text-slate-500 mt-0.5 uppercase tracking-tighter"><?php echo date('M d, H:i', strtotime($ticket['created_at'])); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs font-semibold text-slate-300 max-w-xs truncate"><?php echo htmlspecialchars($ticket['subject']); ?></div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border <?php echo $cls; ?>">
                                    <?php echo $ticket['status']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="view_ticket?id=<?php echo $ticket['id']; ?>" class="bg-cyan-400/10 hover:bg-cyan-400 text-cyan-400 hover:text-white border border-cyan-400/20 px-4 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all">Reply</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile View -->
        <div class="md:hidden divide-y divide-white/5">
            <?php foreach ($tickets as $ticket): 
                $cls = $status_classes[$ticket['status']] ?? 'bg-slate-500/10 text-slate-400 border-slate-500/20';
            ?>
                <div class="p-4 space-y-3">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-[10px] font-bold text-slate-500">#<?php echo $ticket['id']; ?></span>
                            <div class="text-[10px] text-cyan-400 font-bold"><?php echo htmlspecialchars($ticket['user_name']); ?></div>
                        </div>
                        <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border <?php echo $cls; ?>">
                            <?php echo $ticket['status']; ?>
                        </span>
                    </div>
                    <div>
                        <h4 class="text-white font-bold text-sm line-clamp-1"><?php echo htmlspecialchars($ticket['subject']); ?></h4>
                        <div class="text-[10px] text-slate-500 mt-1"><?php echo date('M d, H:i', strtotime($ticket['created_at'])); ?></div>
                    </div>
                    <div class="pt-2 border-t border-white/5">
                        <a href="view_ticket?id=<?php echo $ticket['id']; ?>" class="block text-center bg-cyan-400 text-white text-[10px] font-black py-2.5 rounded-lg uppercase tracking-widest">Open Conversation</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
