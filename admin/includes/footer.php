        </main>
    </div>

    <!-- Admin Mobile Bottom Navigation -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-[100] glass border-t border-white/5 px-2 py-2 flex justify-around items-center safe-area-inset-bottom">
        <a href="<?php echo BASE_URL; ?>/" class="flex flex-col items-center p-2 text-slate-400 hover:text-cyan-400 transition-colors">
            <i class="fas fa-home text-lg"></i>
            <span class="text-[9px] mt-1 font-bold uppercase tracking-tight">Site</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/admin/orders" class="flex flex-col items-center p-2 text-slate-400 hover:text-cyan-400 transition-colors">
            <i class="fas fa-shopping-cart text-lg"></i>
            <span class="text-[9px] mt-1 font-bold uppercase tracking-tight">Orders</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/admin/products" class="flex flex-col items-center p-2 text-slate-400 hover:text-cyan-400 transition-colors">
            <i class="fas fa-th-list text-lg"></i>
            <span class="text-[9px] mt-1 font-bold uppercase tracking-tight">Services</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/admin/users" class="flex flex-col items-center p-2 text-slate-400 hover:text-cyan-400 transition-colors">
            <i class="fas fa-users text-lg"></i>
            <span class="text-[9px] mt-1 font-bold uppercase tracking-tight">Users</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/admin/settings" class="flex flex-col items-center p-2 text-slate-400 hover:text-cyan-400 transition-colors">
            <i class="fas fa-cog text-lg"></i>
            <span class="text-[9px] mt-1 font-bold uppercase tracking-tight">Settings</span>
        </a>
    </nav>
</body>
</html>
