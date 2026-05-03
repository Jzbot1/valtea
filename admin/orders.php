<?php
require_once 'includes/header.php';

$stmt = $db->query("SELECT o.*, s.name as service_name, u.email as user_email FROM orders o JOIN services s ON o.service_id = s.id JOIN users u ON o.user_id = u.id ORDER BY o.id DESC LIMIT 500");
$orders = $stmt->fetchAll();

$status_classes = [
    'Pending' => 'bg-amber-500/10 text-amber-500 border-amber-500/20',
    'Processing' => 'bg-blue-500/10 text-blue-500 border-blue-500/20',
    'In progress' => 'bg-cyan-500/10 text-cyan-500 border-cyan-500/20',
    'Completed' => 'bg-green-500/10 text-green-500 border-green-500/20',
    'Partial' => 'bg-indigo-500/10 text-indigo-500 border-indigo-500/20',
    'Canceled' => 'bg-red-500/10 text-red-500 border-red-500/20',
    'Refunded' => 'bg-slate-500/10 text-slate-500 border-slate-500/20',
];
?>

<div class="max-w-7xl mx-auto">
    <div class="mb-6 text-center md:text-left">
        <h2 class="text-2xl font-black text-white tracking-tight">Order Management</h2>
        <p class="text-slate-400 text-sm mt-1">Monitor and manage all user orders</p>
    </div>

    <div class="glass-card overflow-hidden shadow-2xl">
        <!-- Desktop View -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-white/5 bg-white/5">
                        <th class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest">ID</th>
                        <th class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest">User/Service</th>
                        <th class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest">Link</th>
                        <th class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Cost</th>
                        <th class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Profit</th>
                        <th class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach ($orders as $order): 
                        $cls = $status_classes[$order['status']] ?? 'bg-slate-500/10 text-slate-400 border-slate-500/20';
                        $profit = $order['charge'] - $order['api_charge'];
                    ?>
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="px-4 py-4 text-xs font-bold text-slate-500">#<?php echo $order['id']; ?></td>
                            <td class="px-4 py-4">
                                <div class="text-[10px] text-cyan-400 font-bold mb-1 truncate max-w-[150px]"><?php echo htmlspecialchars($order['user_email']); ?></div>
                                <div class="max-w-[200px] truncate text-xs font-semibold text-white"><?php echo htmlspecialchars($order['service_name']); ?></div>
                                <div class="text-[9px] text-slate-500 mt-0.5 uppercase tracking-tighter"><?php echo date('M d, H:i', strtotime($order['created_at'])); ?></div>
                            </td>
                            <td class="px-4 py-4">
                                <a href="<?php echo htmlspecialchars($order['link']); ?>" target="_blank" class="text-[10px] text-slate-400 hover:text-white transition-colors truncate max-w-[120px] block font-mono">
                                    <?php echo htmlspecialchars($order['link']); ?>
                                </a>
                            </td>
                            <td class="px-4 py-4 text-xs font-bold text-white text-center">₹<?php echo number_format($order['charge'], 4); ?></td>
                            <td class="px-4 py-4 text-xs font-bold text-green-400 text-center">₹<?php echo number_format($profit, 4); ?></td>
                            <td class="px-4 py-4 text-center">
                                <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border <?php echo $cls; ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile View -->
        <div class="md:hidden divide-y divide-white/5">
            <?php foreach ($orders as $order): 
                $cls = $status_classes[$order['status']] ?? 'bg-slate-500/10 text-slate-400 border-slate-500/20';
                $profit = $order['charge'] - $order['api_charge'];
            ?>
                <div class="p-4 space-y-3">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-[10px] font-bold text-slate-500">#<?php echo $order['id']; ?></span>
                            <div class="text-[10px] text-cyan-400 font-bold truncate max-w-[150px]"><?php echo htmlspecialchars($order['user_email']); ?></div>
                        </div>
                        <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border <?php echo $cls; ?>">
                            <?php echo $order['status']; ?>
                        </span>
                    </div>
                    <div>
                        <h4 class="text-white font-bold text-sm line-clamp-1"><?php echo htmlspecialchars($order['service_name']); ?></h4>
                        <a href="<?php echo htmlspecialchars($order['link']); ?>" target="_blank" class="text-[10px] text-slate-500 truncate block mt-1"><?php echo htmlspecialchars($order['link']); ?></a>
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t border-white/5">
                        <div class="text-[10px] text-slate-500"><?php echo date('M d, H:i', strtotime($order['created_at'])); ?></div>
                        <div class="flex space-x-4">
                            <div class="text-right">
                                <p class="text-[8px] text-slate-500 uppercase font-black">Profit</p>
                                <p class="text-xs font-black text-green-400">₹<?php echo number_format($profit, 2); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-[8px] text-slate-500 uppercase font-black">Charge</p>
                                <p class="text-xs font-black text-white">₹<?php echo number_format($order['charge'], 2); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
