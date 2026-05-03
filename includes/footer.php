        </main>
    </div>

    <!-- Mobile Bottom Navigation -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-[100] glass border-t border-slate-700/50 px-4 py-2 flex justify-between items-center safe-area-inset-bottom">
        <a href="<?php echo BASE_URL; ?>/index" class="flex flex-col items-center p-2 text-slate-400 hover:text-indigo-400 transition-colors">
            <i class="fas fa-plus-circle text-xl"></i>
            <span class="text-[10px] mt-1 font-medium">New Order</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/orders" class="flex flex-col items-center p-2 text-slate-400 hover:text-indigo-400 transition-colors">
            <i class="fas fa-shopping-basket text-xl"></i>
            <span class="text-[10px] mt-1 font-medium">Orders</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/services" class="flex flex-col items-center p-2 text-slate-400 hover:text-indigo-400 transition-colors">
            <i class="fas fa-list-ul text-xl"></i>
            <span class="text-[10px] mt-1 font-medium">Services</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/add_funds" class="flex flex-col items-center p-2 text-slate-400 hover:text-indigo-400 transition-colors">
            <i class="fas fa-wallet text-xl"></i>
            <span class="text-[10px] mt-1 font-medium">Funds</span>
        </a>
    </nav>

    <!-- Footer -->
    <footer class="glass border-t border-slate-700/50 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="col-span-1 md:col-span-2">
                    <h2 class="text-xl font-bold text-white mb-4"><?php echo htmlspecialchars($site_name); ?></h2>
                    <p class="text-slate-400 text-sm max-w-sm mb-6">
                        Welcome to <strong><?php echo htmlspecialchars($site_name); ?></strong>. The world's leading SMM Panel providing high-quality social media marketing services at the most affordable rates. Boost your presence today!
                    </p>
                    <div class="flex space-x-4">
                        <?php if (!empty($site_settings['facebook_url'])): ?>
                            <a href="<?php echo htmlspecialchars($site_settings['facebook_url']); ?>" target="_blank" class="text-slate-500 hover:text-indigo-400 transition-colors"><i class="fab fa-facebook-f"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($site_settings['twitter_url'])): ?>
                            <a href="<?php echo htmlspecialchars($site_settings['twitter_url']); ?>" target="_blank" class="text-slate-500 hover:text-indigo-400 transition-colors"><i class="fab fa-twitter"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($site_settings['instagram_url'])): ?>
                            <a href="<?php echo htmlspecialchars($site_settings['instagram_url']); ?>" target="_blank" class="text-slate-500 hover:text-indigo-400 transition-colors"><i class="fab fa-instagram"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($site_settings['telegram_url'])): ?>
                            <a href="<?php echo htmlspecialchars($site_settings['telegram_url']); ?>" target="_blank" class="text-slate-500 hover:text-indigo-400 transition-colors"><i class="fab fa-telegram-plane"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <h3 class="text-white font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2 text-sm text-slate-400">
                        <li><a href="<?php echo BASE_URL; ?>/index" class="hover:text-indigo-400 transition-colors">New Order</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/services" class="hover:text-indigo-400 transition-colors">Services List</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/api_docs" class="hover:text-indigo-400 transition-colors">API Documentation</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/orders" class="hover:text-indigo-400 transition-colors">Order History</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-white font-semibold mb-4">Support</h3>
                    <ul class="space-y-2 text-sm text-slate-400">
                        <li><a href="#" class="hover:text-indigo-400 transition-colors">Tickets</a></li>
                        <li><a href="#" class="hover:text-indigo-400 transition-colors">Terms of Service</a></li>
                        <li><a href="#" class="hover:text-indigo-400 transition-colors">Privacy Policy</a></li>
                        <?php if ($support_number): ?>
                            <li class="text-indigo-400 font-medium"><?php echo htmlspecialchars($support_number); ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <div class="border-t border-slate-700/30 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center text-xs text-slate-500">
                <div class="mb-4 md:mb-0">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_name); ?>. All rights reserved.</p>
                    <p class="mt-1">
                        Designed and Developed By 
                        <a href="https://wa.me/918730063275" target="_blank" class="text-indigo-400 hover:text-indigo-300 font-semibold transition-colors">
                            Zomuana Sailo
                        </a>
                    </p>
                </div>
                <div class="flex space-x-4">
                    <span>Secure Payments</span>
                    <i class="fab fa-cc-visa text-lg"></i>
                    <i class="fab fa-cc-mastercard text-lg"></i>
                    <i class="fab fa-google-pay text-lg"></i>
                </div>
            </div>
        </div>
    </footer>

    <!-- PWA Install Modal (Force-like) -->
    <div id="pwa-install-banner" class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm hidden animate-in fade-in duration-300">
        <div class="max-w-sm w-full glass border border-indigo-500/30 rounded-3xl p-8 shadow-2xl text-center relative overflow-hidden">
            <!-- Decorative Light -->
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-indigo-600/20 rounded-full blur-2xl"></div>
            
            <div class="w-20 h-20 bg-indigo-600 rounded-3xl p-5 shadow-2xl shadow-indigo-600/40 mx-auto mb-6 flex items-center justify-center">
                <i class="fas fa-mobile-alt text-white text-4xl"></i>
            </div>
            
            <h4 class="text-white font-black text-2xl mb-2">Install App</h4>
            <p class="text-slate-400 text-sm mb-8 leading-relaxed">Install our official app for a faster experience, real-time updates, and easier access to our services.</p>
            
            <div class="flex flex-col space-y-3">
                <button id="pwa-install-btn" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 rounded-2xl shadow-xl shadow-indigo-600/20 transition-all active:scale-95">
                    Install Now
                </button>
                <button id="pwa-close-btn" class="w-full text-slate-500 hover:text-slate-300 text-xs font-bold py-2 uppercase tracking-widest transition-colors">
                    Maybe Later
                </button>
            </div>
        </div>
    </div>

    <!-- Push Notification Prompt (Hidden by default) -->
    <div id="push-prompt" class="fixed top-4 left-1/2 -translate-x-1/2 z-[110] w-full max-w-sm px-4 hidden">
        <div class="glass border border-indigo-500/30 rounded-2xl p-4 shadow-2xl flex flex-col items-center text-center">
            <div class="w-12 h-12 bg-indigo-500/20 text-indigo-400 rounded-full flex items-center justify-center mb-3">
                <i class="fas fa-bell text-xl animate-bounce"></i>
            </div>
            <h4 class="text-white font-bold mb-1 text-sm">Stay Updated!</h4>
            <p class="text-slate-400 text-xs mb-4">Enable push notifications for order updates and exclusive offers.</p>
            <div class="flex space-x-3 w-full">
                <button id="push-allow-btn" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2.5 rounded-xl transition-all">
                    Enable
                </button>
                <button id="push-deny-btn" class="flex-1 bg-slate-700 hover:bg-slate-600 text-white text-xs font-bold py-2.5 rounded-xl transition-all">
                    Later
                </button>
            </div>
        </div>
    </div>

    <script>
        // PWA Registration and Logic
        let deferredPrompt;
        const installBanner = document.getElementById('pwa-install-banner');
        const installBtn = document.getElementById('pwa-install-btn');
        const closeBtn = document.getElementById('pwa-close-btn');

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('<?php echo BASE_URL; ?>/sw.js')
                    .then(reg => console.log('SW Registered'))
                    .catch(err => console.log('SW Registration failed', err));
            });
        }

        let deferredPrompt;
        const installBanner = document.getElementById('pwa-install-banner');
        const installBtn = document.getElementById('pwa-install-btn');
        const closeBtn = document.getElementById('pwa-close-btn');

        // Force show logic
        function showInstallModal() {
            if (!window.matchMedia('(display-mode: standalone)').matches) {
                installBanner.classList.remove('hidden');
            }
        }

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            showInstallModal();
        });

        // If it's iOS or browser hasn't fired the prompt yet, show anyway after 2s
        setTimeout(() => {
            if (!deferredPrompt && !window.matchMedia('(display-mode: standalone)').matches) {
                showInstallModal();
            }
        }, 2000);

        installBtn.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    installBanner.classList.add('hidden');
                }
                deferredPrompt = null;
            } else {
                // Fallback for iOS or other browsers
                alert('To install: Tap the "Share" icon and then "Add to Home Screen"');
                installBanner.classList.add('hidden');
            }
        });

        closeBtn.addEventListener('click', () => {
            installBanner.classList.add('hidden');
            // Show again after 60 seconds to "force" it
            setTimeout(showInstallModal, 60000);
        });

        // Push Notification Logic
        const pushPrompt = document.getElementById('push-prompt');
        const pushAllowBtn = document.getElementById('push-allow-btn');
        const pushDenyBtn = document.getElementById('push-deny-btn');

        function checkNotificationPermission() {
            if (!("Notification" in window)) return;
            
            if (Notification.permission === "default" && !localStorage.getItem('push_prompted')) {
                setTimeout(() => {
                    pushPrompt.classList.remove('hidden');
                }, 5000);
            }
        }

        pushAllowBtn.addEventListener('click', () => {
            Notification.requestPermission().then(permission => {
                pushPrompt.classList.add('hidden');
                localStorage.setItem('push_prompted', 'true');
                if (permission === "granted") {
                    new Notification("Welcome!", {
                        body: "You'll now receive updates directly on your device.",
                        icon: "<?php echo BASE_URL; ?>/assets/images/icon-192.png"
                    });
                }
            });
        });

        pushDenyBtn.addEventListener('click', () => {
            pushPrompt.classList.add('hidden');
            localStorage.setItem('push_prompted', 'true');
        });

        // Initialize notification check
        checkNotificationPermission();

        // Active link highlighting
        document.querySelectorAll('nav a').forEach(link => {
            if (link.href === window.location.href) {
                link.classList.add('bg-slate-800', 'text-white');
                link.classList.remove('text-slate-300');
            }
        });
    </script>
</body>
</html>
