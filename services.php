<?php
require_once 'includes/header.php';

$stmt = $db->query("SELECT s.*, c.name as category_name FROM services s JOIN categories c ON s.category_id = c.id WHERE s.status = 'active' ORDER BY c.sort_order, c.name, s.id");
$services = $stmt->fetchAll();

$grouped = [];
foreach ($services as $service) {
    $grouped[$service['category_name']][] = $service;
}
?>

<div class="glass rounded-2xl p-6 md:p-8 shadow-2xl">
    <h2 class="text-2xl font-bold text-white mb-6">Our Services</h2>

    <?php foreach ($grouped as $category => $catservices): ?>
        <h3 class="text-xl font-semibold text-indigo-400 mt-8 mb-4 border-b border-slate-700/50 pb-2"><?php echo htmlspecialchars($category); ?></h3>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse mb-8">
                <thead>
                    <tr class="text-slate-400 text-sm border-b border-slate-700/50">
                        <th class="pb-3 pr-4 font-medium w-16">ID</th>
                        <th class="pb-3 pr-4 font-medium">Service Name</th>
                        <th class="pb-3 pr-4 font-medium w-32">Rate per 1000</th>
                        <th class="pb-3 pr-4 font-medium w-24">Min / Max</th>
                        <th class="pb-3 font-medium w-24">Details</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    <?php foreach ($catservices as $service): ?>
                        <tr class="border-b border-slate-700/30 hover:bg-slate-800/30 transition-colors">
                            <td class="py-3 pr-4 text-slate-300"><?php echo htmlspecialchars($service['id']); ?></td>
                            <td class="py-3 pr-4 text-white font-medium">
                                <?php echo htmlspecialchars($service['name']); ?>
                                <?php if ($service['description']): ?>
                                    <div id="desc-<?php echo $service['id']; ?>" class="hidden mt-2 p-3 rounded bg-slate-800/50 text-slate-300 text-xs">
                                        <?php echo nl2br(htmlspecialchars($service['description'])); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 pr-4 text-indigo-400 font-semibold">₹<?php echo number_format($service['selling_price'], 4); ?></td>
                            <td class="py-3 pr-4 text-slate-400"><?php echo $service['min'] . ' / ' . $service['max']; ?></td>
                            <td class="py-3">
                                <?php if ($service['description']): ?>
                                    <button onclick="document.getElementById('desc-<?php echo $service['id']; ?>').classList.toggle('hidden')" class="px-3 py-1 bg-slate-700 hover:bg-slate-600 rounded text-xs text-white transition-colors">View</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
