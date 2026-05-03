<?php
require_once 'includes/header.php';
require_once __DIR__ . '/../includes/SmmApi.php';

$message = '';
$messageType = '';

if (isset($_POST['sync_services'])) {
    try {
        $api = new SmmApi();
        $provider_services = $api->services();

        if (!$provider_services || !is_array($provider_services)) {
            throw new Exception("Failed to fetch services from provider.");
        }

        $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'profit_percent'");
        $profit_percent = (float)($stmt->fetchColumn() ?: 5);

        $db->beginTransaction();

        foreach ($provider_services as $service) {
            $api_id = $service->service;
            $name = $service->name;
            $category_name = $service->category;
            $rate = (float)$service->rate;
            $min = $service->min;
            $max = $service->max;
            $type = $service->type;

            // Find or Create Category
            $stmt = $db->prepare("SELECT id FROM categories WHERE name = ?");
            $stmt->execute([$category_name]);
            $category_id = $stmt->fetchColumn();

            if (!$category_id) {
                $stmt = $db->prepare("INSERT INTO categories (name) VALUES (?)");
                $stmt->execute([$category_name]);
                $category_id = $db->lastInsertId();
            }

            // Calculate Selling Price
            $selling_price = $rate + ($rate * ($profit_percent / 100));

            // Check if service exists
            $stmt = $db->prepare("SELECT id FROM services WHERE api_service_id = ?");
            $stmt->execute([$api_id]);
            $existing_id = $stmt->fetchColumn();

            if ($existing_id) {
                $stmt = $db->prepare("UPDATE services SET name = ?, category_id = ?, api_rate = ?, selling_price = ?, min = ?, max = ?, type = ? WHERE id = ?");
                $stmt->execute([$name, $category_id, $rate, $selling_price, $min, $max, $type, $existing_id]);
            } else {
                $stmt = $db->prepare("INSERT INTO services (api_service_id, category_id, name, type, api_rate, selling_price, min, max, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                $stmt->execute([$api_id, $category_id, $name, $type, $rate, $selling_price, $min, $max]);
            }
        }

        $db->commit();
        $message = "Successfully synchronized " . count($provider_services) . " services!";
        $messageType = "success";

    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        $message = "Error: " . $e->getMessage();
        $messageType = "error";
    }
}
?>

<div class="flex items-center justify-between mb-8">
    <h2 class="text-3xl font-black text-white tracking-tight">Services Sync</h2>
    <a href="<?php echo BASE_URL; ?>/admin/products" class="text-slate-400 hover:text-white transition-colors flex items-center">
        <i class="fas fa-arrow-left mr-2"></i> Back to Services
    </a>
</div>

<div class="max-w-3xl">
    <div class="glass border border-slate-700/50 rounded-3xl p-8 shadow-2xl">
        <div class="flex items-center space-x-6 mb-8">
            <div class="w-16 h-16 bg-indigo-600/20 text-indigo-400 rounded-2xl flex items-center justify-center text-2xl">
                <i class="fas fa-sync-alt animate-spin-slow"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-white">Auto-Sync from Provider</h3>
                <p class="text-slate-400 text-sm mt-1">This will fetch all services from your SMM provider and update prices based on your profit settings.</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="p-4 rounded-xl mb-8 <?php echo $messageType === 'success' ? 'bg-green-500/10 border border-green-500/50 text-green-400' : 'bg-red-500/10 border border-red-500/50 text-red-400'; ?>">
                <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="bg-slate-900/50 rounded-2xl p-6 border border-slate-800 mb-8">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-slate-300 font-medium">Profit Margin</span>
                    <?php
                    $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'profit_percent'");
                    $profit = $stmt->fetchColumn() ?: 5;
                    ?>
                    <span class="text-indigo-400 font-bold"><?php echo $profit; ?>%</span>
                </div>
                <p class="text-[10px] text-slate-500 uppercase tracking-widest leading-relaxed">
                    The selling price is automatically calculated as: <br>
                    <span class="text-slate-300 font-mono">Provider Rate + <?php echo $profit; ?>% Profit</span>
                </p>
            </div>

            <button type="submit" name="sync_services" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 rounded-2xl transition-all shadow-lg shadow-indigo-600/20 active:scale-95 flex items-center justify-center space-x-2">
                <i class="fas fa-cloud-download-alt"></i>
                <span>FETCH & SYNC ALL SERVICES</span>
            </button>
        </form>
    </div>
</div>

<style>
@keyframes spin-slow {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.animate-spin-slow {
    animation: spin-slow 8s linear infinite;
}
</style>

<?php require_once 'includes/footer.php'; ?>
