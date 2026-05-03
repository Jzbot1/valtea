<?php
require_once 'includes/header.php';

$message = '';

// Handle Status Toggle
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("UPDATE services SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: products.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("DELETE FROM services WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: products.php?deleted=1");
    exit;
}

// Handle Bulk Price Adjustment
if (isset($_POST['bulk_adjust'])) {
    $new_percent = (float)($_POST['adjustment_percent'] ?? 0);
    $category_id = $_GET['category_id'] ?? '';
    $search = $_GET['search'] ?? '';

    $update_query = "UPDATE services SET selling_price = api_rate + (api_rate * ($new_percent / 100)) WHERE 1=1";
    $update_params = [];

    if ($category_id) {
        $update_query .= " AND category_id = ?";
        $update_params[] = $category_id;
    }
    if ($search) {
        $update_query .= " AND (name LIKE ? OR id = ?)";
        $update_params[] = "%$search%";
        $update_params[] = $search;
    }

    $stmt = $db->prepare($update_query);
    $stmt->execute($update_params);
    $message = "Successfully updated " . $stmt->rowCount() . " service prices with $new_percent% profit!";
}

// Handle Search/Filter
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category_id'] ?? '';

$query = "SELECT s.*, c.name as category_name FROM services s JOIN categories c ON s.category_id = c.id WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (s.name LIKE ? OR s.id = ?)";
    $params[] = "%$search%";
    $params[] = $search;
}

if ($category_filter) {
    $query .= " AND s.category_id = ?";
    $params[] = $category_filter;
}

$query .= " ORDER BY c.name, s.id";

$stmt = $db->prepare($query);
$stmt->execute($params);
$services = $stmt->fetchAll();

$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Calculate Totals
$total_api = 0;
$total_sale = 0;
foreach ($services as $svc) {
    $total_api += $svc['api_rate'];
    $total_sale += $svc['selling_price'];
}
$total_profit = $total_sale - $total_api;
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-white">Services & Pricing Analysis</h2>
    <div class="flex space-x-3">
        <a href="sync.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm transition-colors flex items-center">
            <i class="fas fa-sync mr-2"></i> Sync Services
        </a>
    </div>
</div>

<?php if (isset($message) && $message): ?>
    <div class="p-4 rounded-lg mb-6 bg-indigo-500/10 border border-indigo-500/50 text-indigo-400">
        <i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<!-- Stats Dashboard -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="glass p-5 rounded-2xl border-l-4 border-slate-500">
        <div class="text-slate-400 text-[10px] font-bold uppercase mb-1">API Cost</div>
        <div class="text-xl font-bold text-white">₹<?php echo number_format($total_api, 2); ?></div>
    </div>
    <div class="glass p-5 rounded-2xl border-l-4 border-indigo-500">
        <div class="text-indigo-400 text-[10px] font-bold uppercase mb-1">Sale Value</div>
        <div class="text-xl font-bold text-white">₹<?php echo number_format($total_sale, 2); ?></div>
    </div>
    <div class="glass p-5 rounded-2xl border-l-4 border-green-500">
        <div class="text-green-400 text-[10px] font-bold uppercase mb-1">Profit</div>
        <div class="text-xl font-bold text-white">₹<?php echo number_format($total_profit, 2); ?></div>
    </div>
    <div class="glass p-5 rounded-2xl border border-dashed border-slate-700 flex flex-col justify-center">
        <form method="POST" class="flex items-center space-x-2">
            <input type="number" step="0.1" name="adjustment_percent" placeholder="Profit %" required class="w-20 bg-slate-800 border border-slate-600 rounded px-2 py-1 text-xs text-white focus:outline-none focus:border-indigo-500">
            <button type="submit" name="bulk_adjust" onclick="return confirm('Apply this profit percentage to all visible services?')" class="bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-bold px-2 py-1.5 rounded uppercase transition-colors">
                Apply %
            </button>
        </form>
    </div>
</div>

<div class="glass rounded-2xl p-6 shadow-2xl mb-8">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-xs font-medium text-slate-400 mb-1 uppercase tracking-wider">Search Services</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name or ID..." class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-indigo-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-400 mb-1 uppercase tracking-wider">Filter Category</label>
            <select name="category_id" class="w-full bg-slate-800/50 border border-slate-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-indigo-500">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg transition-colors font-medium">
                <i class="fas fa-filter mr-2"></i> Apply Filters
            </button>
        </div>
    </form>
</div>

<div class="glass rounded-2xl overflow-hidden shadow-2xl">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-slate-400 text-[10px] border-b border-slate-700/50 uppercase tracking-widest">
                    <th class="p-4 font-bold">ID</th>
                    <th class="p-4 font-bold">Service Details</th>
                    <th class="p-4 font-bold">Category</th>
                    <th class="p-4 font-bold">API Rate (Base)</th>
                    <th class="p-4 font-bold">Sale Price</th>
                    <th class="p-4 font-bold">Profit</th>
                    <th class="p-4 font-bold">Status</th>
                    <th class="p-4 font-bold">Actions</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                <?php foreach ($services as $svc): 
                    $profit = $svc['selling_price'] - $svc['api_rate'];
                    $profit_percent = ($svc['api_rate'] > 0) ? ($profit / $svc['api_rate']) * 100 : 0;
                ?>
                    <tr class="border-b border-slate-700/30 hover:bg-slate-800/30 transition-colors group">
                        <td class="p-4 text-slate-500 font-mono text-xs">#<?php echo $svc['id']; ?></td>
                        <td class="p-4">
                            <div class="text-white font-medium group-hover:text-indigo-400 transition-colors"><?php echo htmlspecialchars($svc['name']); ?></div>
                            <div class="text-[10px] text-slate-500 mt-1">API ID: <?php echo $svc['api_service_id']; ?> | <?php echo $svc['min']; ?> - <?php echo $svc['max']; ?></div>
                        </td>
                        <td class="p-4">
                            <span class="text-xs bg-slate-800 text-slate-300 px-2 py-1 rounded border border-slate-700">
                                <?php echo htmlspecialchars($svc['category_name']); ?>
                            </span>
                        </td>
                        <td class="p-4 text-slate-400 font-mono">₹<?php echo number_format($svc['api_rate'], 4); ?></td>
                        <td class="p-4 text-indigo-400 font-bold font-mono">₹<?php echo number_format($svc['selling_price'], 4); ?></td>
                        <td class="p-4">
                            <div class="text-green-400 font-bold font-mono">₹<?php echo number_format($profit, 4); ?></div>
                            <div class="text-[9px] text-green-500/70 font-bold">+<?php echo number_format($profit_percent, 1); ?>%</div>
                        </td>
                        <td class="p-4">
                            <a href="?toggle_status=1&id=<?php echo $svc['id']; ?>" class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-tighter <?php echo $svc['status'] === 'active' ? 'bg-green-500/10 text-green-400 border border-green-500/30' : 'bg-red-500/10 text-red-400 border border-red-500/30'; ?>">
                                <?php echo $svc['status']; ?>
                            </a>
                        </td>
                        <td class="p-4">
                            <div class="flex space-x-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="?toggle_status=1&id=<?php echo $svc['id']; ?>" title="Toggle Visibility" class="text-slate-500 hover:text-white">
                                    <i class="fas <?php echo $svc['status'] === 'active' ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                                </a>
                                <a href="?delete=1&id=<?php echo $svc['id']; ?>" onclick="return confirm('Delete this service?')" title="Delete" class="text-slate-500 hover:text-red-400">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($services)): ?>
                    <tr>
                        <td colspan="8" class="p-12 text-center text-slate-500 italic bg-slate-900/20">
                            <i class="fas fa-search mb-3 text-2xl block"></i>
                            No services found matching your criteria.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
