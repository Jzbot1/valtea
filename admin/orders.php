<?php
require_once 'includes/header.php';

$stmt = $db->query("SELECT o.*, s.name as service_name, u.email as user_email FROM orders o JOIN services s ON o.service_id = s.id JOIN users u ON o.user_id = u.id ORDER BY o.id DESC LIMIT 500");
$orders = $stmt->fetchAll();
?>

<div class="glass rounded-2xl p-6 md:p-8 shadow-2xl">
    <h2 class="text-2xl font-bold text-white mb-6">All Orders</h2>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-slate-400 text-sm border-b border-slate-700/50">
                    <th class="pb-3 pr-4 font-medium">ID</th>
                    <th class="pb-3 pr-4 font-medium">User</th>
                    <th class="pb-3 pr-4 font-medium">Service</th>
                    <th class="pb-3 pr-4 font-medium">Link</th>
                    <th class="pb-3 pr-4 font-medium">Qty</th>
                    <th class="pb-3 pr-4 font-medium">Charge</th>
                    <th class="pb-3 pr-4 font-medium">Profit</th>
                    <th class="pb-3 font-medium">Status</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                <?php foreach ($orders as $order): ?>
                    <tr class="border-b border-slate-700/30 hover:bg-slate-800/30 transition-colors">
                        <td class="py-3 pr-4 text-slate-400"><?php echo htmlspecialchars($order['id']); ?></td>
                        <td class="py-3 pr-4 text-slate-300"><?php echo htmlspecialchars($order['user_email']); ?></td>
                        <td class="py-3 pr-4 text-white font-medium max-w-xs truncate"><?php echo htmlspecialchars($order['service_name']); ?></td>
                        <td class="py-3 pr-4 text-indigo-400 truncate max-w-[150px]"><a href="<?php echo htmlspecialchars($order['link']); ?>" target="_blank" class="hover:underline"><?php echo htmlspecialchars($order['link']); ?></a></td>
                        <td class="py-3 pr-4 text-slate-300"><?php echo htmlspecialchars($order['quantity']); ?></td>
                        <td class="py-3 pr-4 text-slate-300">₹<?php echo number_format($order['charge'], 4); ?></td>
                        <td class="py-3 pr-4 text-green-400 font-semibold">₹<?php echo number_format($order['charge'] - $order['api_charge'], 4); ?></td>
                        <td class="py-3">
                            <?php 
                                $statusColors = [
                                    'Pending' => 'bg-yellow-500/10 text-yellow-500 border-yellow-500/50',
                                    'Processing' => 'bg-blue-500/10 text-blue-500 border-blue-500/50',
                                    'Completed' => 'bg-green-500/10 text-green-500 border-green-500/50',
                                    'Partial' => 'bg-orange-500/10 text-orange-500 border-orange-500/50',
                                    'Canceled' => 'bg-red-500/10 text-red-500 border-red-500/50',
                                ];
                                $colorClass = $statusColors[$order['status']] ?? 'bg-slate-500/10 text-slate-400 border-slate-500/50';
                            ?>
                            <span class="px-2 py-1 rounded-full text-xs font-medium border <?php echo $colorClass; ?>">
                                <?php echo htmlspecialchars($order['status']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
