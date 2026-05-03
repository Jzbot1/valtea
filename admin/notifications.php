<?php
require_once 'includes/header.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_notification'])) {
        $title = $_POST['title'];
        $msg = $_POST['message'];
        
        $stmt = $db->prepare("INSERT INTO notifications (title, message) VALUES (?, ?)");
        $stmt->execute([$title, $msg]);
        $message = "Notification added!";
    } elseif (isset($_POST['delete_notification'])) {
        $id = $_POST['id'];
        $stmt = $db->prepare("DELETE FROM notifications WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Notification deleted!";
    }
}

$stmt = $db->query("SELECT * FROM notifications ORDER BY id DESC");
$notifications = $stmt->fetchAll();
?>

<div class="max-w-4xl mx-auto space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-black text-white tracking-tight">Announcements</h2>
            <p class="text-slate-400 mt-1">Manage global messages for your users.</p>
        </div>
        <div class="w-12 h-12 bg-indigo-600/10 rounded-2xl flex items-center justify-center text-indigo-500">
            <i class="fas fa-bullhorn text-xl"></i>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="p-4 rounded-2xl bg-green-500/10 border border-green-500/20 text-green-400 flex items-start space-x-3 text-sm animate-in fade-in slide-in-from-top-4">
            <i class="fas fa-check-circle mt-0.5"></i>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Create Notification -->
        <div class="lg:col-span-1">
            <div class="glass border border-slate-700/50 rounded-3xl p-6 shadow-2xl sticky top-24">
                <h3 class="text-lg font-bold text-white mb-6 flex items-center">
                    <span class="w-8 h-8 bg-indigo-600/20 text-indigo-400 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-plus text-xs"></i>
                    </span>
                    Create New
                </h3>
                
                <form method="POST" action="" class="space-y-5">
                    <input type="hidden" name="add_notification" value="1">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Title</label>
                        <input type="text" name="title" required placeholder="e.g. System Update" class="w-full bg-slate-900/50 border border-slate-700/50 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-indigo-500 transition-all text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Message</label>
                        <textarea name="message" rows="4" required placeholder="Type your message here..." class="w-full bg-slate-900/50 border border-slate-700/50 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-indigo-500 transition-all text-sm"></textarea>
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-indigo-600/20 transition-all active:scale-95 flex items-center justify-center space-x-2">
                        <i class="fas fa-paper-plane text-xs"></i>
                        <span>Publish Now</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Notification List -->
        <div class="lg:col-span-2">
            <div class="glass border border-slate-700/50 rounded-3xl p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-white mb-6 flex items-center">
                    <span class="w-8 h-8 bg-indigo-600/20 text-indigo-400 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-history text-xs"></i>
                    </span>
                    History
                </h3>

                <div class="space-y-4">
                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-12">
                            <div class="w-16 h-16 bg-slate-800/50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-700/30">
                                <i class="fas fa-inbox text-slate-600"></i>
                            </div>
                            <p class="text-slate-500 text-sm">No announcements yet.</p>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($notifications as $notif): ?>
                        <div class="p-5 bg-slate-900/40 border border-slate-700/50 rounded-2xl group hover:border-indigo-500/30 transition-all">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h4 class="text-white font-bold mb-1 flex items-center">
                                        <?php echo htmlspecialchars($notif['title']); ?>
                                        <?php if ($notif['status'] === 'active'): ?>
                                            <span class="ml-3 w-1.5 h-1.5 rounded-full bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.6)]"></span>
                                        <?php endif; ?>
                                    </h4>
                                    <p class="text-slate-400 text-xs leading-relaxed"><?php echo nl2br(htmlspecialchars($notif['message'])); ?></p>
                                    <div class="mt-4 flex items-center space-x-4">
                                        <span class="text-[10px] text-slate-600 font-mono flex items-center">
                                            <i class="far fa-clock mr-1.5"></i>
                                            <?php echo date('M d, Y • H:i', strtotime($notif['created_at'])); ?>
                                        </span>
                                    </div>
                                </div>
                                <form method="POST" action="" onsubmit="return confirm('Delete this announcement?');" class="ml-4">
                                    <input type="hidden" name="delete_notification" value="1">
                                    <input type="hidden" name="id" value="<?php echo $notif['id']; ?>">
                                    <button type="submit" class="w-8 h-8 rounded-lg bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-all flex items-center justify-center opacity-0 group-hover:opacity-100">
                                        <i class="fas fa-trash text-[10px]"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
