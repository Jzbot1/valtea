<?php
require_once 'includes/header.php';

$ticket_id = $_GET['id'] ?? null;
if (!$ticket_id) { header("Location: tickets"); exit; }

// Fetch Ticket
$stmt = $db->prepare("
    SELECT t.*, u.name as user_name 
    FROM tickets t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.id = ?
");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

if (!$ticket) { header("Location: tickets"); exit; }

// Handle Status Update
if (isset($_POST['update_status'])) {
    $new_status = $_POST['status'] ?? 'Open';
    $stmt = $db->prepare("UPDATE tickets SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $ticket_id]);
    header("Location: view_ticket?id=" . $ticket_id);
    exit;
}

// Handle Admin Reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message'])) {
    $msg = $_POST['reply_message'] ?? '';
    if ($msg) {
        $stmt = $db->prepare("INSERT INTO ticket_replies (ticket_id, user_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$ticket_id, $_SESSION['user_id'], $msg]);
        
        // Mark as pending (waiting for user)
        $stmt = $db->prepare("UPDATE tickets SET status = 'Pending' WHERE id = ?");
        $stmt->execute([$ticket_id]);
        
        header("Location: view_ticket?id=" . $ticket_id);
        exit;
    }
}

// Fetch Replies
$stmt = $db->prepare("
    SELECT tr.*, u.name as user_name, u.role as user_role 
    FROM ticket_replies tr 
    JOIN users u ON tr.user_id = u.id 
    WHERE tr.ticket_id = ? 
    ORDER BY tr.created_at ASC
");
$stmt->execute([$ticket_id]);
$replies = $stmt->fetchAll();
?>

<div class="flex items-center justify-between mb-8">
    <h2 class="text-3xl font-black text-white tracking-tight">Manage Ticket #<?php echo $ticket['id']; ?></h2>
    <a href="tickets" class="text-slate-400 hover:text-white transition-colors flex items-center">
        <i class="fas fa-arrow-left mr-2"></i> Back to Tickets
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Conversation -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Original Message -->
        <div class="glass border border-slate-700/50 rounded-3xl p-6 shadow-xl">
            <div class="flex items-center space-x-4 mb-4">
                <div class="w-10 h-10 bg-slate-700 rounded-full flex items-center justify-center text-white text-sm font-bold">
                    <?php echo substr($ticket['user_name'], 0, 1); ?>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-white"><?php echo htmlspecialchars($ticket['user_name']); ?></h4>
                    <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest"><?php echo date('M d, H:i', strtotime($ticket['created_at'])); ?></span>
                </div>
            </div>
            <p class="text-slate-300 text-sm leading-relaxed whitespace-pre-wrap"><?php echo htmlspecialchars($ticket['message']); ?></p>
        </div>

        <!-- Replies -->
        <?php foreach ($replies as $reply): ?>
            <div class="glass border <?php echo $reply['user_role'] === 'admin' ? 'border-indigo-500/40 bg-indigo-500/5' : 'border-slate-700/50'; ?> rounded-3xl p-6 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-4">
                        <div class="w-10 h-10 <?php echo $reply['user_role'] === 'admin' ? 'bg-indigo-600' : 'bg-slate-700'; ?> rounded-full flex items-center justify-center text-white text-sm font-bold">
                            <?php echo substr($reply['user_name'], 0, 1); ?>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-white">
                                <?php echo htmlspecialchars($reply['user_name']); ?>
                                <?php if ($reply['user_role'] === 'admin'): ?>
                                    <span class="ml-2 bg-indigo-600 text-[8px] font-black uppercase tracking-tighter px-1.5 py-0.5 rounded-md">Staff</span>
                                <?php endif; ?>
                            </h4>
                            <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest"><?php echo date('M d, H:i', strtotime($reply['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
                <p class="text-slate-300 text-sm leading-relaxed whitespace-pre-wrap"><?php echo htmlspecialchars($reply['message']); ?></p>
            </div>
        <?php endforeach; ?>

        <!-- Reply Form -->
        <div class="glass border border-slate-700/50 rounded-3xl p-8 shadow-2xl mt-10">
            <form method="POST" action="">
                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-4 px-1">Write a Reply</label>
                <textarea name="reply_message" rows="4" required class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-indigo-500 transition-all mb-6"></textarea>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-xl transition-all shadow-lg shadow-indigo-600/20 active:scale-95">
                    SEND REPLY AS STAFF
                </button>
            </form>
        </div>
    </div>

    <!-- Ticket Sidebar -->
    <div class="space-y-6">
        <div class="glass border border-slate-700/50 rounded-3xl p-6 shadow-xl">
            <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-6 border-b border-slate-700/50 pb-2">Control Panel</h3>
            
            <form method="POST" action="" class="space-y-6">
                <div>
                    <label class="block text-[10px] text-slate-500 font-bold uppercase tracking-widest mb-2 px-1">Update Status</label>
                    <select name="status" class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-indigo-500">
                        <option value="Open" <?php echo $ticket['status'] === 'Open' ? 'selected' : ''; ?>>Open</option>
                        <option value="Pending" <?php echo $ticket['status'] === 'Pending' ? 'selected' : ''; ?>>Pending (Waiting for User)</option>
                        <option value="Closed" <?php echo $ticket['status'] === 'Closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>
                <button type="submit" name="update_status" class="w-full bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold py-3 rounded-xl transition-all border border-slate-700">
                    UPDATE STATUS
                </button>
            </form>

            <div class="mt-8 pt-8 border-t border-slate-700/50 space-y-4">
                <div>
                    <label class="block text-[10px] text-slate-500 font-bold uppercase tracking-widest mb-1">User Details</label>
                    <p class="text-sm font-bold text-white"><?php echo htmlspecialchars($ticket['user_name']); ?></p>
                </div>
                <div>
                    <label class="block text-[10px] text-slate-500 font-bold uppercase tracking-widest mb-1">Created Date</label>
                    <p class="text-xs text-slate-400"><?php echo date('M d, Y H:i', strtotime($ticket['created_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
