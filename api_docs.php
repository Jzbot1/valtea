<?php
require_once 'includes/header.php';
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$api_url = FULL_URL . "/api/v1/";
?>

<div class="max-w-5xl mx-auto space-y-10 mb-20">
    <!-- Header -->
    <div class="text-center">
        <h2 class="text-4xl font-black text-white mb-3">API Documentation</h2>
        <p class="text-slate-400">Automate your workflow with our high-performance REST API.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        <!-- Documentation Content -->
        <div class="lg:col-span-2 space-y-8">
            <div class="glass-card rounded-3xl p-8 border border-white/5">
                <div class="flex items-center space-x-4 mb-8">
                    <div class="w-12 h-12 bg-indigo-600/20 text-indigo-400 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-key text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-white font-bold">Authentication</h3>
                        <p class="text-slate-500 text-xs uppercase tracking-widest font-bold">Your Personal Access Token</p>
                    </div>
                </div>

                <div class="bg-slate-950/50 border border-slate-700/50 rounded-2xl p-5 flex items-center justify-between">
                    <code id="api-key" class="text-indigo-400 font-mono text-sm break-all mr-4"><?php echo htmlspecialchars($user['api_key']); ?></code>
                    <button onclick="copyApiKey()" class="flex-shrink-0 w-10 h-10 bg-indigo-600/10 hover:bg-indigo-600 text-indigo-400 hover:text-white rounded-xl transition-all flex items-center justify-center">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                <p class="mt-4 text-[10px] text-slate-500 italic">Keep this key secret! It provides full access to your account funds and orders.</p>
            </div>

            <!-- API Endpoints -->
            <div class="glass-card rounded-3xl p-8 border border-white/5">
                <h3 class="text-xl font-bold text-white mb-8">Endpoints</h3>
                
                <div class="space-y-10">
                    <!-- Balance -->
                    <div class="group">
                        <div class="flex items-center mb-4">
                            <span class="bg-green-500/10 text-green-500 text-[10px] font-black px-2 py-1 rounded-md border border-green-500/20 mr-4">POST</span>
                            <h4 class="text-white font-bold text-sm uppercase tracking-tight">Get User Balance</h4>
                        </div>
                        <div class="bg-slate-950/80 rounded-xl p-4 font-mono text-xs text-slate-400 border border-slate-800 mb-4 group-hover:border-indigo-500/30 transition-colors">
                            <?php echo $api_url; ?>balance
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-xs">
                                <p class="text-slate-500 font-bold mb-1">Parameters</p>
                                <ul class="text-slate-300 list-disc list-inside">
                                    <li>api_key</li>
                                </ul>
                            </div>
                            <div class="text-xs">
                                <p class="text-slate-500 font-bold mb-1">Response</p>
                                <pre class="text-indigo-400/80">{ "status": "success", "balance": 100.00 }</pre>
                            </div>
                        </div>
                    </div>

                    <!-- Services -->
                    <div class="group pt-8 border-t border-slate-800">
                        <div class="flex items-center mb-4">
                            <span class="bg-green-500/10 text-green-500 text-[10px] font-black px-2 py-1 rounded-md border border-green-500/20 mr-4">POST</span>
                            <h4 class="text-white font-bold text-sm uppercase tracking-tight">Get Service List</h4>
                        </div>
                        <div class="bg-slate-950/80 rounded-xl p-4 font-mono text-xs text-slate-400 border border-slate-800 group-hover:border-indigo-500/30 transition-colors">
                            <?php echo $api_url; ?>services
                        </div>
                    </div>

                    <!-- Create Order -->
                    <div class="group pt-8 border-t border-slate-800">
                        <div class="flex items-center mb-4">
                            <span class="bg-green-500/10 text-green-500 text-[10px] font-black px-2 py-1 rounded-md border border-green-500/20 mr-4">POST</span>
                            <h4 class="text-white font-bold text-sm uppercase tracking-tight">Create New Order</h4>
                        </div>
                        <div class="bg-slate-950/80 rounded-xl p-4 font-mono text-xs text-slate-400 border border-slate-800 mb-4 group-hover:border-indigo-500/30 transition-colors">
                            <?php echo $api_url; ?>order
                        </div>
                        <div class="text-xs">
                            <p class="text-slate-500 font-bold mb-1">Parameters</p>
                            <ul class="text-slate-300 space-y-1">
                                <li><code>api_key</code> - Required</li>
                                <li><code>service</code> - Service ID (int)</li>
                                <li><code>link</code> - Social link (string)</li>
                                <li><code>quantity</code> - Number of units (int)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- API Tester Sidebar -->
        <div class="lg:col-span-1">
            <div class="glass-card rounded-3xl p-8 border border-white/5 sticky top-24">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="w-10 h-10 bg-green-500/10 text-green-500 rounded-xl flex items-center justify-center">
                        <i class="fas fa-vial"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white">API Tester</h3>
                </div>
                
                <div class="space-y-6">
                    <p class="text-slate-400 text-xs leading-relaxed">Test your connection instantly by sending a real balance request.</p>
                    
                    <button id="test-api-btn" onclick="testApi()" class="w-full btn-primary text-white font-bold py-4 rounded-2xl shadow-xl transition-all active:scale-95 flex items-center justify-center">
                        <span>Test Balance Request</span>
                        <i class="fas fa-bolt ml-2 text-xs"></i>
                    </button>

                    <div id="test-result-container" class="hidden animate-in zoom-in-95 duration-300">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2 px-1">Result</p>
                        <pre id="test-result" class="bg-slate-950 border border-slate-700 rounded-2xl p-4 text-[10px] font-mono text-green-400 overflow-x-auto whitespace-pre-wrap"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyApiKey() {
    const key = document.getElementById('api-key').innerText;
    navigator.clipboard.writeText(key).then(() => {
        alert('API Key copied to clipboard!');
    });
}

async function testApi() {
    const btn = document.getElementById('test-api-btn');
    const resultContainer = document.getElementById('test-result-container');
    const resultPre = document.getElementById('test-result');
    const apiKey = '<?php echo $user['api_key']; ?>';

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>';
    
    try {
        const response = await fetch('<?php echo $api_url; ?>balance', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'api_key=' + apiKey
        });
        
        const data = await response.json();
        resultPre.innerText = JSON.stringify(data, null, 2);
        resultPre.className = data.status === 'success' ? 
            'bg-slate-950 border border-green-500/30 rounded-2xl p-4 text-[10px] font-mono text-green-400 overflow-x-auto whitespace-pre-wrap' : 
            'bg-slate-950 border border-red-500/30 rounded-2xl p-4 text-[10px] font-mono text-red-400 overflow-x-auto whitespace-pre-wrap';
            
    } catch (error) {
        resultPre.innerText = 'Connection Error: ' + error.message;
        resultPre.className = 'bg-slate-950 border border-red-500/30 rounded-2xl p-4 text-[10px] font-mono text-red-400 overflow-x-auto whitespace-pre-wrap';
    } finally {
        resultContainer.classList.remove('hidden');
        btn.disabled = false;
        btn.innerHTML = '<span>Test Balance Request</span><i class="fas fa-bolt ml-2 text-xs"></i>';
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
