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
?>

<div class="glass-card rounded-3xl p-6 md:p-8 shadow-2xl overflow-hidden">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h2 class="text-2xl font-black text-white">Order History</h2>
            <p class="text-slate-400 text-sm">Track your recent social media boosts</p>
        </div>
        <a href="/smm/index.php" class="btn-primary text-white text-xs font-bold px-5 py-2.5 rounded-xl flex items-center">
            <i class="fas fa-plus mr-2"></i> New Order
        </a>
    </div>

    <?php if ($message): ?>
        <div class="p-4 rounded-2xl mb-6 flex items-start space-x-3 <?php echo $messageType === 'success' ? 'bg-green-500/10 border-green-500/30 text-green-400' : 'bg-red-500/10 border-red-500/30 text-red-400'; ?> border animate-in fade-in slide-in-from-top-4 duration-300">
            <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
            <div class="text-sm font-medium"><?php echo htmlspecialchars($message); ?></div>
        </div>
    <?php endif; ?>

    <!-- Desktop Table -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="text-slate-500 text-[10px] uppercase tracking-widest border-b border-slate-700/50">
                    <th class="pb-4 px-4 font-bold">ID</th>
                    <th class="pb-4 px-4 font-bold">Service</th>
                    <th class="pb-4 px-4 font-bold">Link</th>
                    <th class="pb-4 px-4 font-bold">Charge</th>
                    <th class="pb-4 px-4 font-bold">Qty</th>
                    <th class="pb-4 px-4 font-bold text-center">Status</th>
                    <th class="pb-4 px-4 font-bold text-right">Action</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                <?php foreach ($orders as $order): 
                    $statusColors = [
                        'Pending' => 'bg-yellow-500/10 text-yellow-500 border-yellow-500/20',
                        'Processing' => 'bg-blue-500/10 text-blue-500 border-blue-500/20',
                        'Completed' => 'bg-green-500/10 text-green-500 border-green-500/20',
                        'Partial' => 'bg-orange-500/10 text-orange-500 border-orange-500/20',
                        'Canceled' => 'bg-red-500/10 text-red-500 border-red-500/20',
                    ];
                    $colorClass = $statusColors[$order['status']] ?? 'bg-slate-500/10 text-slate-400 border-slate-500/20';
                ?>
                    <tr class="border-b border-slate-700/30 hover:bg-slate-800/20 transition-colors group">
                        <td class="py-4 px-4 text-slate-400 font-mono text-xs">#<?php echo $order['id']; ?></td>
                        <td class="py-4 px-4">
                            <div class="text-white font-semibold max-w-[200px] truncate"><?php echo htmlspecialchars($order['service_name']); ?></div>
                        </td>
                        <td class="py-4 px-4 text-indigo-400/80 truncate max-w-[150px]">
                            <a href="<?php echo htmlspecialchars($order['link']); ?>" target="_blank" class="hover:text-indigo-300 transition-colors flex items-center">
                                <i class="fas fa-link text-[10px] mr-2"></i>
                                <?php echo htmlspecialchars($order['link']); ?>
                            </a>
                        </td>
                        <td class="py-4 px-4 text-white font-bold">₹<?php echo number_format($order['charge'], 2); ?></td>
                        <td class="py-4 px-4 text-slate-300 font-medium"><?php echo number_format($order['quantity']); ?></td>
                        <td class="py-4 px-4 text-center">
                            <span class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-tighter border <?php echo $colorClass; ?>">
                                <?php echo htmlspecialchars($order['status']); ?>
                            </span>
                        </td>
                        <td class="py-4 px-4 text-right">
                            <?php if ($order['status'] === 'Completed'): ?>
                                <form method="POST" action="" class="inline">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <button type="submit" name="refill_order" class="text-[10px] font-bold text-indigo-400 hover:text-indigo-300 uppercase tracking-widest transition-colors">
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

    <!-- Mobile Cards -->
    <div class="md:hidden space-y-4">
        <?php foreach ($orders as $order): 
            $colorClass = $statusColors[$order['status']] ?? 'bg-slate-500/10 text-slate-400 border-slate-500/20';
        ?>
            <div class="p-5 bg-slate-900/40 border border-slate-700/50 rounded-2xl relative overflow-hidden">
                <div class="flex justify-between items-start mb-3">
                    <span class="text-slate-500 font-mono text-[10px]">#<?php echo $order['id']; ?></span>
                    <div class="flex flex-col items-end gap-2">
                        <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-black uppercase border <?php echo $colorClass; ?>">
                            <?php echo $order['status']; ?>
                        </span>
                        <?php if ($order['status'] === 'Completed'): ?>
                            <form method="POST" action="">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <button type="submit" name="refill_order" class="bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-400 text-[10px] font-black py-1 px-3 rounded-md uppercase transition-all">
                                    Refill
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <h4 class="text-white font-bold text-sm mb-2 line-clamp-1"><?php echo htmlspecialchars($order['service_name']); ?></h4>
                <div class="flex items-center text-indigo-400 text-xs mb-4">
                    <i class="fas fa-link mr-2 text-[10px]"></i>
                    <a href="<?php echo $order['link']; ?>" class="truncate"><?php echo $order['link']; ?></a>
                </div>
                <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-700/50">
                    <div>
                        <p class="text-slate-500 text-[10px] uppercase font-bold tracking-widest mb-1">Charge</p>
                        <p class="text-white font-black">₹<?php echo number_format($order['charge'], 2); ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-slate-500 text-[10px] uppercase font-bold tracking-widest mb-1">Quantity</p>
                        <p class="text-white font-black"><?php echo number_format($order['quantity']); ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if(empty($orders)): ?>
        <div class="py-20 text-center">
            <div class="w-20 h-20 bg-slate-800/50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-700/50">
                <i class="fas fa-shopping-basket text-slate-600 text-2xl"></i>
            </div>
            <p class="text-slate-500 font-medium">No orders found in your history.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
