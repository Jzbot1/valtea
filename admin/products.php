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
    $message = "Successfully updated " . $stmt->rowCount() . " services with $new_percent% profit!";
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

$total_api = 0;
$total_sale = 0;
foreach ($services as $svc) {
    $total_api += $svc['api_rate'];
    $total_sale += $svc['selling_price'];
}
$total_profit = $total_sale - $total_api;
?>

<div class="max-w-7xl mx-auto">
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="text-center md:text-left">
            <h2 class="text-2xl font-black text-white tracking-tight">Service Catalog</h2>
            <p class="text-slate-400 text-sm mt-1">Pricing analysis and service management</p>
        </div>
        <div class="flex gap-2">
            <a href="sync" class="btn-primary text-white py-2.5 px-6 rounded-xl flex items-center justify-center">
                <i class="fas fa-sync mr-2 text-[10px]"></i> SYNC ALL
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="p-4 rounded-xl mb-6 flex items-start space-x-3 bg-green-500/10 border border-green-500/30 text-green-400 text-sm">
            <i class="fas fa-check-circle mt-0.5"></i>
            <div><?php echo htmlspecialchars($message); ?></div>
        </div>
    <?php endif; ?>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="glass-card p-4 border-l-4 border-slate-500">
            <p class="text-[9px] text-slate-500 uppercase font-black tracking-widest mb-1">Total API Cost</p>
            <p class="text-lg font-black text-white">₹<?php echo number_format($total_api, 2); ?></p>
        </div>
        <div class="glass-card p-4 border-l-4 border-cyan-400">
            <p class="text-[9px] text-slate-500 uppercase font-black tracking-widest mb-1">Total Sale Value</p>
            <p class="text-lg font-black text-white">₹<?php echo number_format($total_sale, 2); ?></p>
        </div>
        <div class="glass-card p-4 border-l-4 border-green-500">
            <p class="text-[9px] text-slate-500 uppercase font-black tracking-widest mb-1">Est. Portfolio Profit</p>
            <p class="text-lg font-black text-green-400">₹<?php echo number_format($total_profit, 2); ?></p>
        </div>
        <div class="glass-card p-4 flex flex-col justify-center">
            <form method="POST" class="flex gap-2">
                <input type="number" step="0.1" name="adjustment_percent" placeholder="Profit %" required class="flex-1 !py-2 !text-[11px]">
                <button type="submit" name="bulk_adjust" onclick="return confirm('Apply this profit percentage to all visible services?')" class="bg-cyan-400 text-white text-[9px] font-black px-3 rounded-lg uppercase">Apply</button>
            </form>
        </div>
    </div>

    <!-- Filters -->
    <div class="glass-card p-6 mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5 px-1">Search</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="ID or name..." class="w-full !py-2.5">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5 px-1">Category</label>
                <select name="category_id" class="w-full !py-2.5">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-white/5 hover:bg-white/10 text-white py-2.5 rounded-xl text-xs font-bold transition-all uppercase tracking-widest">
                    <i class="fas fa-filter mr-2"></i> Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Main Table -->
    <div class="glass-card overflow-hidden shadow-2xl">
        <!-- Desktop Table -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-white/5 bg-white/5">
                        <th class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest">ID</th>
                        <th class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest">Service Name</th>
                        <th class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest">API Rate</th>
                        <th class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest">Sale Price</th>
                        <th class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest">Profit</th>
                        <th class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest">Status</th>
                        <th class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach ($services as $svc): 
                        $profit = $svc['selling_price'] - $svc['api_rate'];
                        $profit_percent = ($svc['api_rate'] > 0) ? ($profit / $svc['api_rate']) * 100 : 0;
                    ?>
                        <tr class="hover:bg-white/5 transition-colors group">
                            <td class="px-4 py-4 text-xs font-bold text-slate-500">#<?php echo $svc['id']; ?></td>
                            <td class="px-4 py-4">
                                <div class="text-xs font-bold text-white mb-1 truncate max-w-[250px] group-hover:text-cyan-400 transition-colors"><?php echo htmlspecialchars($svc['name']); ?></div>
                                <div class="text-[9px] text-slate-500 uppercase tracking-widest">Category: <?php echo htmlspecialchars($svc['category_name']); ?></div>
                            </td>
                            <td class="px-4 py-4 text-xs text-slate-400 font-mono">₹<?php echo number_format($svc['api_rate'], 4); ?></td>
                            <td class="px-4 py-4 text-xs text-cyan-400 font-black font-mono">₹<?php echo number_format($svc['selling_price'], 4); ?></td>
                            <td class="px-4 py-4">
                                <div class="text-xs text-green-400 font-black">₹<?php echo number_format($profit, 4); ?></div>
                                <div class="text-[9px] text-green-500/50 font-bold">+<?php echo number_format($profit_percent, 1); ?>%</div>
                            </td>
                            <td class="px-4 py-4">
                                <a href="?toggle_status=1&id=<?php echo $svc['id']; ?>" class="px-2 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border <?php echo $svc['status'] === 'active' ? 'bg-green-500/10 text-green-400 border-green-500/20' : 'bg-red-500/10 text-red-400 border-red-500/20'; ?>">
                                    <?php echo $svc['status']; ?>
                                </a>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <div class="flex items-center justify-end space-x-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a href="?delete=1&id=<?php echo $svc['id']; ?>" onclick="return confirm('Delete?')" class="text-slate-500 hover:text-red-400 transition-colors"><i class="fas fa-trash-alt text-xs"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile View -->
        <div class="md:hidden divide-y divide-white/5">
            <?php foreach ($services as $svc): 
                $profit = $svc['selling_price'] - $svc['api_rate'];
                $profit_percent = ($svc['api_rate'] > 0) ? ($profit / $svc['api_rate']) * 100 : 0;
            ?>
                <div class="p-4 space-y-3">
                    <div class="flex justify-between items-start">
                        <span class="text-[10px] font-bold text-slate-500">#<?php echo $svc['id']; ?></span>
                        <a href="?toggle_status=1&id=<?php echo $svc['id']; ?>" class="px-2 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border <?php echo $svc['status'] === 'active' ? 'bg-green-500/10 text-green-400 border-green-500/20' : 'bg-red-500/10 text-red-400 border-red-500/20'; ?>">
                            <?php echo $svc['status']; ?>
                        </a>
                    </div>
                    <div>
                        <h4 class="text-white font-bold text-sm line-clamp-2"><?php echo htmlspecialchars($svc['name']); ?></h4>
                        <div class="text-[9px] text-slate-500 uppercase tracking-widest mt-1"><?php echo htmlspecialchars($svc['category_name']); ?></div>
                    </div>
                    <div class="flex justify-between items-center pt-3 border-t border-white/5">
                        <div class="flex gap-4">
                            <div>
                                <p class="text-[8px] text-slate-500 uppercase font-black">API</p>
                                <p class="text-[10px] font-bold text-slate-400">₹<?php echo number_format($svc['api_rate'], 4); ?></p>
                            </div>
                            <div>
                                <p class="text-[8px] text-slate-500 uppercase font-black">Sale</p>
                                <p class="text-[10px] font-black text-cyan-400">₹<?php echo number_format($svc['selling_price'], 4); ?></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-[8px] text-slate-500 uppercase font-black">Profit</p>
                            <p class="text-xs font-black text-green-400">+₹<?php echo number_format($profit, 4); ?></p>
                            <p class="text-[8px] text-green-500/70 font-bold"><?php echo number_format($profit_percent, 1); ?>%</p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
