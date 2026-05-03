<?php
require_once 'includes/header.php';
require_once 'includes/SmmApi.php';

$message = '';
$messageType = '';

if (isset($_POST['refill_order'])) {
    $order_id = $_POST['order_id'];
    $stmt = $db->prepare("SELECT api_order_id FROM orders WHERE id = ? AND user_id = ? AND status = 'Completed'");
    $stmt->execute([$order_id, $user['id']]);
    $order = $stmt->fetch();

    if ($order && $order['api_order_id']) {
        $api = new SmmApi();
        $response = $api->refill($order['api_order_id']);
        
        if (isset($response->refill)) {
            $message = "Refill request sent successfully! Refill ID: " . $response->refill;
            $messageType = "success";
        } elseif (isset($response->error)) {
            $message = "Error: " . $response->error;
            $messageType = "error";
        } else {
            $message = "Refill not available for this order yet.";
            $messageType = "error";
        }
    } else {
        $message = "Invalid order or status.";
        $messageType = "error";
    }
}

$stmt = $db->prepare("SELECT o.*, s.name as service_name FROM orders o JOIN services s ON o.service_id = s.id WHERE o.user_id = ? ORDER BY o.id DESC LIMIT 100");
$stmt->execute([$user['id']]);
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

<div class="max-w-6xl mx-auto">
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="text-center md:text-left">
            <h2 class="text-2xl font-black text-white tracking-tight">Order History</h2>
            <p class="text-slate-400 text-sm mt-1">Manage and track your social boosts</p>
        </div>
        <a href="<?php echo BASE_URL; ?>/index" class="btn-primary text-white py-2.5 px-6 rounded-xl flex items-center justify-center">
            <i class="fas fa-plus mr-2 text-[10px]"></i> NEW ORDER
        </a>
    </div>

    <?php if ($message): ?>
        <div class="p-4 rounded-xl mb-6 flex items-start space-x-3 <?php echo $messageType === 'success' ? 'bg-green-500/10 border-green-500/30 text-green-400' : 'bg-red-500/10 border-red-500/30 text-red-400'; ?> border text-sm">
            <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
            <div><?php echo htmlspecialchars($message); ?></div>
        </div>
    <?php endif; ?>

    <div class="glass-card overflow-hidden shadow-2xl">
        <!-- Desktop View -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-white/5 bg-white/5">
                        <th class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest">ID</th>
                        <th class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest">Service</th>
                        <th class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest">Link</th>
                        <th class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Cost</th>
                        <th class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Qty</th>
                        <th class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Status</th>
                        <th class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="7" class="px-4 py-12 text-center text-slate-500 text-sm italic">No orders found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($orders as $order): 
                        $cls = $status_classes[$order['status']] ?? 'bg-slate-500/10 text-slate-400 border-slate-500/20';
                    ?>
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="px-4 py-4 text-xs font-bold text-slate-500">#<?php echo $order['id']; ?></td>
                            <td class="px-4 py-4">
                                <div class="max-w-[200px] truncate text-xs font-semibold text-white"><?php echo htmlspecialchars($order['service_name']); ?></div>
                                <div class="text-[9px] text-slate-500 mt-0.5 uppercase tracking-tighter"><?php echo date('M d, H:i', strtotime($order['created_at'])); ?></div>
                            </td>
                            <td class="px-4 py-4">
                                <a href="<?php echo htmlspecialchars($order['link']); ?>" target="_blank" class="text-[10px] text-cyan-400 hover:underline truncate max-w-[120px] block font-mono">
                                    <?php echo htmlspecialchars($order['link']); ?>
                                </a>
                            </td>
                            <td class="px-4 py-4 text-xs font-bold text-white text-center">₹<?php echo number_format($order['charge'], 2); ?></td>
                            <td class="px-4 py-4 text-xs font-bold text-white text-center"><?php echo number_format($order['quantity']); ?></td>
                            <td class="px-4 py-4 text-center">
                                <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border <?php echo $cls; ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <?php if ($order['status'] === 'Completed'): ?>
                                    <form method="POST" action="" class="inline">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <button type="submit" name="refill_order" class="text-[10px] font-black text-cyan-400 hover:text-white transition-colors uppercase tracking-widest">
                                            Refill
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-[10px] text-slate-600 uppercase tracking-widest">N/A</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile View -->
        <div class="md:hidden divide-y divide-white/5">
            <?php if (empty($orders)): ?>
                <div class="px-4 py-12 text-center text-slate-500 text-sm italic">No orders found.</div>
            <?php endif; ?>
            <?php foreach ($orders as $order): 
                $cls = $status_classes[$order['status']] ?? 'bg-slate-500/10 text-slate-400 border-slate-500/20';
            ?>
                <div class="p-4 space-y-3">
                    <div class="flex justify-between items-start">
                        <span class="text-[10px] font-bold text-slate-500">#<?php echo $order['id']; ?></span>
                        <div class="flex flex-col items-end gap-2">
                            <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border <?php echo $cls; ?>">
                                <?php echo $order['status']; ?>
                            </span>
                            <?php if ($order['status'] === 'Completed'): ?>
                                <form method="POST" action="">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <button type="submit" name="refill_order" class="bg-cyan-400/10 text-cyan-400 text-[9px] font-black py-1 px-3 rounded-lg uppercase transition-all">
                                        Refill
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-white font-bold text-sm line-clamp-1"><?php echo htmlspecialchars($order['service_name']); ?></h4>
                        <a href="<?php echo htmlspecialchars($order['link']); ?>" target="_blank" class="text-[10px] text-cyan-400 font-mono truncate block mt-1"><?php echo htmlspecialchars($order['link']); ?></a>
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t border-white/5">
                        <div class="text-[10px] text-slate-500"><?php echo date('M d, H:i', strtotime($order['created_at'])); ?></div>
                        <div class="flex space-x-4">
                            <div class="text-right">
                                <p class="text-[8px] text-slate-500 uppercase font-black">Charge</p>
                                <p class="text-xs font-black text-white">₹<?php echo number_format($order['charge'], 2); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-[8px] text-slate-500 uppercase font-black">Qty</p>
                                <p class="text-xs font-black text-white"><?php echo number_format($order['quantity']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
