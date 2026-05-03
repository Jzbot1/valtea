<?php
require_once 'includes/header.php';
require_once __DIR__ . '/../includes/SmmApi.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $api = new SmmApi();
    $services = $api->services();

    if (isset($services->error)) {
        $message = "API Error: " . $services->error;
    } else {
        $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'profit_percent'");
        $profit_percent = (float)$stmt->fetchColumn() ?: 5;

        $db->beginTransaction();
        
        // Deactivate all services first, we will reactivate what's synced
        $db->query("UPDATE services SET status = 'inactive'");
        
        $categories_map = []; // name => id
        $stmt = $db->query("SELECT id, name FROM categories");
        while ($row = $stmt->fetch()) {
            $categories_map[$row['name']] = $row['id'];
        }

        $cat_sort = 1;
        $sync_count = 0;

        foreach ($services as $svc) {
            $cat_name = $svc->category;
            if (!isset($categories_map[$cat_name])) {
                $stmt = $db->prepare("INSERT INTO categories (name, sort_order) VALUES (?, ?)");
                $stmt->execute([$cat_name, $cat_sort++]);
                $cat_id = $db->lastInsertId();
                $categories_map[$cat_name] = $cat_id;
            } else {
                $cat_id = $categories_map[$cat_name];
            }

            $api_rate = (float)$svc->rate;
            $selling_price = $api_rate + ($api_rate * ($profit_percent / 100));

            // Check if service exists
            $stmt = $db->prepare("SELECT id FROM services WHERE api_service_id = ?");
            $stmt->execute([$svc->service]);
            $existing = $stmt->fetch();

            if ($existing) {
                // update
                $stmt = $db->prepare("UPDATE services SET category_id = ?, name = ?, type = ?, api_rate = ?, selling_price = ?, min = ?, max = ?, description = ?, status = 'active' WHERE api_service_id = ?");
                $stmt->execute([$cat_id, $svc->name, $svc->type, $api_rate, $selling_price, $svc->min, $svc->max, $svc->desc ?? '', $svc->service]);
            } else {
                // insert
                $stmt = $db->prepare("INSERT INTO services (api_service_id, category_id, name, type, api_rate, selling_price, min, max, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                $stmt->execute([$svc->service, $cat_id, $svc->name, $svc->type, $api_rate, $selling_price, $svc->min, $svc->max, $svc->desc ?? '']);
            }
            $sync_count++;
        }

        $db->commit();
        $message = "Successfully synced $sync_count services!";
    }
}
?>

<div class="glass rounded-2xl p-6 md:p-8 shadow-2xl max-w-2xl">
    <h2 class="text-2xl font-bold text-white mb-6">Sync Services</h2>
    <p class="text-slate-400 mb-6">This will fetch all services from your SMM provider, create categories, and apply your global profit margin automatically.</p>

    <?php if ($message): ?>
        <div class="p-4 rounded-lg mb-6 <?php echo strpos($message, 'Successfully') !== false ? 'bg-green-500/10 border border-green-500/50 text-green-400' : 'bg-red-500/10 border border-red-500/50 text-red-400'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <button type="submit" onclick="return confirm('Are you sure you want to sync all services now?')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 px-6 rounded-lg transition-colors shadow-lg shadow-indigo-600/30 flex items-center">
            <i class="fas fa-sync mr-2"></i> Start Sync
        </button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
