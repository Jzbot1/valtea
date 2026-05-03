<?php
require_once 'includes/header.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_ticket'])) {
    $subject = $_POST['subject'] ?? '';
    $msg = $_POST['message'] ?? '';

    if ($subject && $msg) {
        $stmt = $db->prepare("INSERT INTO tickets (user_id, subject, message) VALUES (?, ?, ?)");
        $stmt->execute([$user['id'], $subject, $msg]);
        $message = "Ticket created successfully! We will reply soon.";
        $messageType = "success";
    }
}

$stmt = $db->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$tickets = $stmt->fetchAll();
?>

<div class="flex items-center justify-between mb-8">
    <h2 class="text-3xl font-black text-white tracking-tight">Support Tickets</h2>
    <button onclick="document.getElementById('newTicketModal').classList.remove('hidden')" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-3 px-6 rounded-xl transition-all shadow-lg shadow-indigo-600/20 active:scale-95">
        OPEN NEW TICKET
    </button>
</div>

<?php if ($message): ?>
    <div class="p-4 rounded-xl mb-8 bg-green-500/10 border border-green-500/50 text-green-400">
        <i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="glass border border-slate-700/50 rounded-3xl overflow-hidden shadow-2xl">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-slate-800/50">
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">ID</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Subject</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Date</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-700/50">
            <?php foreach ($tickets as $ticket): ?>
                <tr class="hover:bg-slate-800/30 transition-colors">
                    <td class="px-6 py-4 text-sm font-mono text-slate-500">#<?php echo $ticket['id']; ?></td>
                    <td class="px-6 py-4 text-sm font-bold text-white"><?php echo htmlspecialchars($ticket['subject']); ?></td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-tighter <?php 
                            echo $ticket['status'] === 'Open' ? 'bg-green-500/10 text-green-400 border border-green-500/20' : 
                                ($ticket['status'] === 'Pending' ? 'bg-yellow-500/10 text-yellow-400 border border-yellow-500/20' : 
                                'bg-slate-500/10 text-slate-400 border border-slate-500/20'); 
                        ?>">
                            <?php echo $ticket['status']; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-xs text-slate-400"><?php echo date('M d, Y', strtotime($ticket['created_at'])); ?></td>
                    <td class="px-6 py-4">
                        <a href="view_ticket?id=<?php echo $ticket['id']; ?>" class="text-indigo-400 hover:text-white transition-colors text-sm font-bold">
                            View <i class="fas fa-chevron-right ml-1"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($tickets)): ?>
                <tr>
                    <td colspan="5" class="px-6 py-20 text-center text-slate-500 italic">No support tickets found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- New Ticket Modal -->
<div id="newTicketModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-sm">
    <div class="glass border-slate-700 w-full max-w-lg rounded-3xl p-8 shadow-2xl scale-in">
        <div class="flex items-center justify-between mb-8">
            <h3 class="text-xl font-bold text-white">Create New Ticket</h3>
            <button onclick="document.getElementById('newTicketModal').classList.add('hidden')" class="text-slate-500 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" action="">
            <div class="mb-6">
                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 px-1">Subject</label>
                <input type="text" name="subject" required class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-indigo-500 transition-all">
            </div>
            <div class="mb-8">
                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 px-1">Message</label>
                <textarea name="message" rows="5" required class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-indigo-500 transition-all"></textarea>
            </div>
            <button type="submit" name="create_ticket" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 rounded-2xl transition-all shadow-lg shadow-indigo-600/20 active:scale-95">
                SUBMIT TICKET
            </button>
        </form>
    </div>
</div>

<style>
@keyframes scaleIn {
    from { transform: scale(0.95); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
.scale-in { animation: scaleIn 0.2s cubic-bezier(0.4, 0, 0.2, 1); }
</style>

<?php require_once 'includes/footer.php'; ?>
