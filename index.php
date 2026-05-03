<?php
require_once 'includes/header.php';
require_once 'includes/Telegram.php';

$stmt = $db->query("SELECT * FROM categories ORDER BY sort_order ASC, name ASC");
$categories = $stmt->fetchAll();

$stmt = $db->query("SELECT * FROM services WHERE status = 'active'");
$services = $stmt->fetchAll();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = $_POST['service'] ?? null;
    $link = $_POST['link'] ?? '';
    $quantity = $_POST['quantity'] ?? 0;

    if ($service_id && $link && $quantity) {
        $ch = curl_init(FULL_URL . '/api/v1/order');
        
        // Wait, the host might not be localhost/smm. It's better to implement order logic directly or via a shared class. Let's do it directly here for simplicity, or just include a helper.
        // I will implement the logic directly to avoid curl issues on different hosts.
        
        $stmt = $db->prepare("SELECT * FROM services WHERE id = ? AND status = 'active'");
        $stmt->execute([$service_id]);
        $service = $stmt->fetch();

        if ($service) {
            if ($quantity >= $service['min'] && $quantity <= $service['max']) {
                $charge = ($service['selling_price'] / 1000) * $quantity;
                if ($user['balance'] >= $charge) {
                    try {
                        $db->beginTransaction();
                        
                        $new_balance = $user['balance'] - $charge;
                        $stmt = $db->prepare("UPDATE users SET balance = ? WHERE id = ?");
                        $stmt->execute([$new_balance, $user['id']]);

                        $stmt = $db->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'debit', ?)");
                        $stmt->execute([$user['id'], $charge, "Order placed for service ID $service_id"]);

                        require_once 'includes/SmmApi.php';
                        $api = new SmmApi();
                        $api_response = $api->order([
                            'service' => $service['api_service_id'],
                            'link' => $link,
                            'quantity' => $quantity
                        ]);

                        if (isset($api_response->error)) {
                            throw new Exception("Provider API Error: " . $api_response->error);
                        }

                        $api_order_id = $api_response->order ?? null;
                        if (!$api_order_id) throw new Exception("Failed to get order ID");

                        $api_charge = ($service['api_rate'] / 1000) * $quantity;
                        $stmt = $db->prepare("INSERT INTO orders (user_id, service_id, api_order_id, link, quantity, charge, api_charge, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
                        $stmt->execute([$user['id'], $service_id, $api_order_id, $link, $quantity, $charge, $api_charge]);

                        $db->commit();
                        $message = 'Order placed successfully!';
                        $messageType = 'success';

                        // Send Telegram Notification
                        Telegram::sendOrderNotification($db->lastInsertId(), $service['name'], $link, $quantity, $charge, $user['email']);

                        // Send Email Invoice
                        require_once 'includes/Mailer.php';
                        Mailer::sendInvoice($user['email'], [
                            'id' => $db->lastInsertId(),
                            'service_name' => $service['name'],
                            'quantity' => $quantity,
                            'charge' => $charge,
                            'link' => $link
                        ]);
                        
                        // refresh user balance
                        $user['balance'] = $new_balance;

                    } catch (Exception $e) {
                        $db->rollBack();
                        $message = $e->getMessage();
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Insufficient balance';
                    $messageType = 'error';
                }
            } else {
                $message = "Quantity must be between {$service['min']} and {$service['max']}";
                $messageType = 'error';
            }
        } else {
            $message = 'Invalid service';
            $messageType = 'error';
        }
    }
}
// Fetch latest active notification
$stmt = $db->query("SELECT * FROM notifications WHERE status = 'active' ORDER BY id DESC LIMIT 1");
$announcement = $stmt->fetch();
?>

<div class="max-w-2xl mx-auto">
    <!-- Welcome Section -->
    <div class="mb-8">
        <h2 class="text-3xl font-black text-white">Hey, <?php echo explode('@', $user['email'])[0]; ?>! 👋</h2>
        <p class="text-slate-400 mt-1">Ready to boost your social presence?</p>
    </div>

    <div class="glass-card rounded-3xl p-6 md:p-10 shadow-2xl relative overflow-hidden">
        <!-- Decorative Gradient -->
        <div class="absolute -top-24 -right-24 w-48 h-48 bg-indigo-600/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-blue-600/10 rounded-full blur-3xl"></div>

        <h3 class="text-xl font-bold text-white mb-8 flex items-center">
            <span class="w-8 h-8 bg-indigo-600/20 text-indigo-400 rounded-lg flex items-center justify-center mr-3">
                <i class="fas fa-magic text-sm"></i>
            </span>
            Create New Order
        </h3>

        <?php if ($message): ?>
            <div class="p-4 rounded-2xl mb-8 flex items-start space-x-3 <?php echo $messageType === 'success' ? 'bg-green-500/10 border-green-500/30 text-green-400' : 'bg-red-500/10 border-red-500/30 text-red-400'; ?> border animate-in fade-in slide-in-from-top-4 duration-300">
                <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
                <div class="text-sm font-medium"><?php echo htmlspecialchars($message); ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-6">
            <div class="group">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Category</label>
                <div class="relative">
                    <select id="category" class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl px-5 py-4 text-white appearance-none focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all cursor-pointer">
                        <option value="">Select a category...</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-500">
                        <i class="fas fa-chevron-down text-xs"></i>
                    </div>
                </div>
            </div>

            <div class="group">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Service</label>
                <div class="relative">
                    <select name="service" id="service" required class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl px-5 py-4 text-white appearance-none focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all cursor-pointer">
                        <option value="">Select a service...</option>
                    </select>
                    <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-500">
                        <i class="fas fa-chevron-down text-xs"></i>
                    </div>
                </div>
            </div>

            <div id="service_desc" class="hidden p-5 rounded-2xl bg-indigo-500/5 border border-indigo-500/20 text-indigo-200/80 text-xs leading-relaxed italic">
            </div>

            <div class="group">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Link / URL</label>
                <input type="text" name="link" required class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl px-5 py-4 text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all" placeholder="Enter social profile or post link">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="group">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Quantity</label>
                    <input type="number" name="quantity" id="quantity" required class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl px-5 py-4 text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all" placeholder="e.g. 1000">
                    <p id="min_max_info" class="text-[10px] text-slate-500 mt-2 px-1 font-medium"></p>
                </div>

                <div class="bg-indigo-500/10 border border-indigo-500/20 rounded-2xl p-4 flex flex-col justify-center items-center">
                    <span class="text-[10px] text-indigo-300 uppercase font-bold tracking-widest mb-1">Total Charge</span>
                    <span id="charge" class="text-2xl font-black text-white">₹0.00</span>
                </div>
            </div>

            <button type="submit" class="w-full btn-primary text-white font-bold py-5 rounded-2xl shadow-xl active:scale-[0.98] mt-4 flex items-center justify-center space-x-2">
                <span>Confirm Order</span>
                <i class="fas fa-arrow-right text-xs"></i>
            </button>
        </form>
    </div>

    <!-- Admin Announcement Section -->
    <?php if ($announcement): ?>
        <div class="mt-10 animate-in fade-in slide-in-from-bottom-6 duration-700 delay-300">
            <div class="relative group">
                <!-- Glowing Border Effect -->
                <div class="absolute -inset-0.5 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-3xl blur opacity-20 group-hover:opacity-40 transition duration-1000 group-hover:duration-200"></div>
                
                <div class="relative glass-card rounded-3xl p-6 md:p-8 border border-white/5 overflow-hidden">
                    <div class="flex items-start space-x-5">
                        <div class="w-12 h-12 bg-indigo-600/20 text-indigo-400 rounded-2xl flex items-center justify-center flex-shrink-0 animate-pulse">
                            <i class="fas fa-bullhorn text-lg"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex justify-between items-center mb-2">
                                <h4 class="text-white font-black uppercase tracking-wider text-sm"><?php echo htmlspecialchars($announcement['title']); ?></h4>
                                <span class="text-[10px] text-slate-500 font-bold uppercase tracking-tighter"><?php echo date('M d', strtotime($announcement['created_at'])); ?></span>
                            </div>
                            <p class="text-slate-400 text-sm leading-relaxed font-medium">
                                <?php echo nl2br(htmlspecialchars($announcement['message'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    const services = <?php echo json_encode($services); ?>;
    const categorySelect = document.getElementById('category');
    const serviceSelect = document.getElementById('service');
    const descBox = document.getElementById('service_desc');
    const quantityInput = document.getElementById('quantity');
    const chargeDisplay = document.getElementById('charge');
    const minMaxInfo = document.getElementById('min_max_info');

    categorySelect.addEventListener('change', function() {
        const catId = this.value;
        serviceSelect.innerHTML = '<option value="">Select Service...</option>';
        descBox.classList.add('hidden');
        chargeDisplay.innerText = '₹0.00';
        
        services.filter(s => s.category_id == catId).forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = `${s.id} - ${s.name} - ₹${parseFloat(s.selling_price).toFixed(2)}`;
            serviceSelect.appendChild(opt);
        });
    });

    serviceSelect.addEventListener('change', updateCharge);
    quantityInput.addEventListener('input', updateCharge);

    function updateCharge() {
        const serviceId = serviceSelect.value;
        const quantity = parseInt(quantityInput.value) || 0;
        
        if (serviceId) {
            const service = services.find(s => s.id == serviceId);
            if (service) {
                if (service.description) {
                    descBox.innerHTML = service.description.replace(/\n/g, '<br>');
                    descBox.classList.remove('hidden');
                } else {
                    descBox.classList.add('hidden');
                }

                minMaxInfo.textContent = `Min: ${service.min} - Max: ${service.max}`;
                
                const rate = parseFloat(service.selling_price) / 1000;
                const charge = rate * quantity;
                chargeDisplay.textContent = '₹' + charge.toFixed(2);
            }
        } else {
            descBox.classList.add('hidden');
            minMaxInfo.textContent = '';
            chargeDisplay.textContent = '₹0.00';
        }
    }
</script>

<?php require_once 'includes/footer.php'; ?>
