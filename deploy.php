<?php
/**
 * RedOcean Deployment Console (Compact Version)
 * ==========================================
 * Features: Deploy, Revert, Detailed Commit Info, Karachi Timezone.
 * Security: Password-based access ("123") with persistent sessions.
 * Fix: Post-Redirect-Get pattern to stop "Resubmit Form" popups.
 * UI Update: In-place bubble confirmation next to buttons.
 */

session_start();
date_default_timezone_set('Asia/Karachi');

// ==========================================
// 1. CONFIGURATION
// ==========================================

$repo_path  = '/home/noorgeec/repositories/law-redocean';
$work_tree  = '/home/noorgeec/noorgee.pk/Law';
$branch     = 'main-lw';
$access_pass = '123'; 
$github_url = 'https://github.com/grapheart247/law-redocean';

// ==========================================
// 2. ACCESS CONTROL
// ==========================================

if (isset($_POST['login_pass']) && $_POST['login_pass'] === $access_pass) {
    $_SESSION['ro_authorized'] = true;
    header("Location: deploy.php");
    exit;
}

if (!isset($_SESSION['ro_authorized']) || $_SESSION['ro_authorized'] !== true) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>RedOcean | Login</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </head>
    <body class="bg-[#0f172a] h-screen flex items-center justify-center p-4">
        <form method="POST" class="bg-[#161b22] p-8 rounded-xl border border-slate-800 shadow-2xl w-full max-w-sm">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-700">
                    <i class="fa-solid fa-shield-halved text-green-400 text-2xl"></i>
                </div>
                <h2 class="text-white font-bold text-xl uppercase tracking-tight">RedOcean Login</h2>
                <p class="text-slate-500 text-xs mt-1 italic">Enter '123' to manage Law Repo</p>
            </div>
            <input type="password" name="login_pass" placeholder="Password" autofocus
                   class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white mb-4 focus:border-green-500 outline-none transition text-center tracking-widest">
            <button type="submit" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-3 rounded-lg transition uppercase text-sm tracking-widest">
                Unlock Console
            </button>
        </form>
    </body>
    </html>
    <?php
    exit;
}

// ==========================================
// 3. ACTION LOGIC
// ==========================================

function execute_command($cmd, $title) {
    $output = [];
    $return_var = 0;
    $full_cmd = "$cmd 2>&1";
    exec($full_cmd, $output, $return_var);
    return [
        'title'   => $title,
        'command' => $cmd,
        'output'  => implode("\n", $output),
        'status'  => $return_var === 0
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    $temp_logs = [];

    if (is_dir($repo_path)) chdir($repo_path);

    if ($action === 'status') {
        $temp_logs[] = execute_command("git status", "System Check");
    } 
    elseif ($action === 'deploy') {
        $temp_logs[] = execute_command("git fetch origin $branch", "Fetching Updates");
        $temp_logs[] = execute_command("git reset --hard origin/$branch", "Syncing with GitHub");
        $deploy_cmd = "git --git-dir=$repo_path/.git --work-tree=$work_tree checkout -f $branch";
        $temp_logs[] = execute_command($deploy_cmd, "Live Extraction");
    }
    elseif ($action === 'revert') {
        $temp_logs[] = execute_command("git reset --hard HEAD~1", "Rolling Back 1 Commit");
        $deploy_cmd = "git --git-dir=$repo_path/.git --work-tree=$work_tree checkout -f $branch";
        $temp_logs[] = execute_command($deploy_cmd, "Restoring Previous Files to Live");
    }
    elseif ($action === 'logout') {
        session_destroy();
        header("Location: https://noorgee.pk/Law");
        exit;
    }

    $_SESSION['last_logs'] = $temp_logs;
    header("Location: deploy.php");
    exit;
}

$logs = $_SESSION['last_logs'] ?? [];
unset($_SESSION['last_logs']);

function get_commit_details($path) {
    $cmd = "git -C $path log -1 --format='Hash: %h%nAuthor: %an%nDate: %ci%nSubject: %s%nDescription: %b'";
    $res = @shell_exec($cmd);
    return $res ? htmlspecialchars($res) : "No commit data found.";
}

$current_commit = get_commit_details($repo_path);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RedOcean | Deployment Console</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Inter:wght@300;400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; overflow: hidden; }
        .font-mono { font-family: 'JetBrains Mono', monospace; white-space: pre-wrap; }
        .terminal-bg { background: #0d1117; }
        .custom-scroll::-webkit-scrollbar { width: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: #0d1117; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #30363d; border-radius: 3px; }
        
        @keyframes slideInUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .bubble-msg { animation: slideInUp 0.3s ease-out forwards; }
        
        /* Tooltip Arrow */
        .confirm-bubble::after {
            content: "";
            position: absolute;
            top: -10px;
            right: 20px;
            border-left: 10px solid transparent;
            border-right: 10px solid transparent;
            border-bottom: 10px solid white;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 h-screen flex flex-col">

    <!-- Notification Area (Top Right) -->
    <div id="notification-area" class="fixed top-14 right-6 z-50 flex flex-col gap-3 pointer-events-none"></div>

    <!-- Header -->
    <nav class="bg-slate-900 text-white shadow-lg flex-none">
        <div class="max-w-7xl mx-auto px-4 h-12 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-server text-green-400 text-sm"></i>
                <h1 class="font-bold text-sm tracking-tight uppercase">RedOcean Ops <span class="text-slate-500 font-normal">v2.4</span></h1>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-[10px] font-mono text-slate-400 uppercase tracking-widest hidden md:inline">Karachi: <?php echo date('H:i:s'); ?></span>
                <button onclick="location.reload()" class="p-1.5 hover:bg-slate-800 rounded transition text-slate-400" title="Refresh Page">
                    <i class="fa-solid fa-rotate text-xs"></i>
                </button>
                <div class="relative inline-block">
                    <form method="POST" id="logout-form">
                        <input type="hidden" name="action" value="logout">
                        <button type="button" onclick="toggleConfirm(this, 'logout')" class="p-1.5 hover:text-red-400 transition text-slate-400" title="Logout & Exit">
                            <i class="fa-solid fa-right-from-bracket text-xs"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 py-4 flex-1 flex flex-col gap-4 overflow-hidden w-full">
        
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 flex-none">
            
            <div class="md:col-span-8 bg-white rounded-lg border border-slate-200 shadow-sm flex flex-col overflow-hidden">
                <div class="bg-slate-50 px-3 py-1.5 border-b border-slate-200 flex justify-between items-center">
                    <span class="text-[10px] font-bold uppercase text-slate-500">Active Commit Metadata</span>
                    <span class="text-[9px] bg-green-100 text-green-700 px-2 py-0.5 rounded font-bold">PRODUCTION</span>
                </div>
                <div class="p-3 bg-slate-50/50">
                    <div class="font-mono text-[10px] text-slate-600 leading-tight grid grid-cols-1 md:grid-cols-2 gap-x-4">
                        <?php 
                        $lines = explode("\n", $current_commit);
                        foreach($lines as $line) {
                            if(trim($line)) echo "<span>" . $line . "</span>";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="md:col-span-4 relative">
                <!-- Action Buttons Container -->
                <div class="flex flex-col gap-2 h-full">
                    <div class="grid grid-cols-2 gap-2">
                        <form method="POST" id="deploy-form" class="relative">
                            <input type="hidden" name="action" value="deploy">
                            <button type="button" onclick="toggleConfirm(this, 'deploy')" class="w-full h-full py-3 bg-green-600 hover:bg-green-700 text-white text-xs font-bold rounded transition flex flex-col items-center justify-center gap-1">
                                <i class="fa-solid fa-cloud-arrow-up"></i> DEPLOY
                            </button>
                        </form>
                        <form method="POST" id="revert-form" class="relative">
                            <input type="hidden" name="action" value="revert">
                            <button type="button" onclick="toggleConfirm(this, 'revert')" class="w-full h-full py-3 bg-white border border-red-200 text-red-600 hover:bg-red-50 text-xs font-bold rounded transition flex flex-col items-center justify-center gap-1">
                                <i class="fa-solid fa-clock-rotate-left"></i> REVERT
                            </button>
                        </form>
                    </div>
                    <form method="POST" id="status-form">
                        <input type="hidden" name="action" value="status">
                        <button type="submit" class="w-full py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded transition text-[10px] uppercase">
                            <i class="fa-solid fa-magnifying-glass mr-1"></i> System Status Check
                        </button>
                    </form>
                </div>

                <!-- Shared Floating Confirm Bubble (Dynamic Position) -->
                <div id="confirm-popover" class="hidden absolute z-[100] right-0 mt-2 w-64 bg-white rounded-xl shadow-2xl border border-slate-200 p-4 bubble-msg confirm-bubble">
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-triangle-exclamation text-sm"></i>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-slate-900" id="pop-title">Confirm?</h4>
                                <p class="text-[10px] text-slate-500" id="pop-desc">Proceed with this action?</p>
                            </div>
                        </div>
                        <div class="flex gap-2 border-t border-slate-100 pt-3">
                            <button onclick="hideConfirm()" class="flex-1 py-1.5 text-[10px] font-bold text-slate-500 bg-slate-50 hover:bg-slate-100 rounded transition">No, Cancel</button>
                            <button id="pop-btn" class="flex-1 py-1.5 text-[10px] font-bold text-white bg-green-600 hover:bg-green-700 rounded transition">Yes, Run</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Terminal Output -->
        <div class="terminal-bg rounded-lg shadow-xl border border-slate-800 flex flex-col flex-1 min-h-0">
            <div class="bg-[#161b22] px-3 py-1.5 border-b border-slate-800 flex items-center justify-between">
                <div class="flex gap-1">
                    <div class="w-2 h-2 rounded-full bg-red-500/80"></div>
                    <div class="w-2 h-2 rounded-full bg-yellow-500/80"></div>
                    <div class="w-2 h-2 rounded-full bg-green-500/80"></div>
                </div>
                <span class="text-[9px] font-mono text-slate-500 uppercase tracking-widest">Console Output Stream</span>
            </div>
            
            <div class="p-4 overflow-y-auto flex-1 font-mono text-[11px] custom-scroll">
                <?php if (empty($logs)): ?>
                    <div class="h-full flex flex-col items-center justify-center text-slate-800 italic opacity-50">
                        <p>> Awaiting operations...</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <div class="mb-4">
                            <div class="flex items-center gap-2 mb-0.5">
                                <span class="<?php echo $log['status'] ? 'text-green-500' : 'text-red-500'; ?> font-bold">
                                    [<?php echo $log['status'] ? 'SUCCESS' : 'ERROR'; ?>]
                                </span>
                                <span class="text-slate-500 uppercase text-[9px]"><?php echo $log['title']; ?></span>
                            </div>
                            <div class="text-blue-500/80 mb-1">$ <?php echo $log['command']; ?></div>
                            <div class="text-slate-300 pl-3 border-l border-slate-800/50 py-1 bg-slate-900/30 rounded"><?php echo nl2br(htmlspecialchars($log['output'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="bg-slate-100 border-t border-slate-200 px-4 py-1 flex justify-between items-center text-[9px] text-slate-400">
        <p>Path: <?php echo $work_tree; ?></p>
        <p>&copy; RedOcean Services - Secure Console</p>
    </footer>

    <script>
        function notify(msg, type = 'success') {
            const area = document.getElementById('notification-area');
            const bubble = document.createElement('div');
            bubble.className = `bubble-msg pointer-events-auto flex items-center gap-3 px-4 py-2 rounded-lg shadow-xl border ${type === 'success' ? 'bg-green-600 border-green-500 text-white' : 'bg-red-600 border-red-500 text-white'} min-w-[200px]`;
            
            const icon = type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
            bubble.innerHTML = `<i class="fa-solid ${icon} text-sm"></i> <span class="text-[10px] font-bold uppercase tracking-wider">${msg}</span>`;
            
            area.appendChild(bubble);

            setTimeout(() => {
                bubble.style.opacity = '0';
                bubble.style.transform = 'translateY(-10px)';
                bubble.style.transition = 'all 0.5s ease';
                setTimeout(() => bubble.remove(), 500);
            }, 10000);
        }

        // Toggle the confirm bubble next to the clicked button
        function toggleConfirm(el, action) {
            const popover = document.getElementById('confirm-popover');
            const btn = document.getElementById('pop-btn');
            const title = document.getElementById('pop-title');
            const desc = document.getElementById('pop-desc');

            // Setup content
            if (action === 'deploy') {
                title.innerText = "Deploy Changes?";
                desc.innerText = "Fetch GitHub code & update Live site.";
                btn.className = "flex-1 py-1.5 text-[10px] font-bold text-white bg-green-600 hover:bg-green-700 rounded transition";
                btn.onclick = () => document.getElementById('deploy-form').submit();
            } else if (action === 'revert') {
                title.innerText = "Revert Site?";
                desc.innerText = "Go back 1 commit. Current code will be lost.";
                btn.className = "flex-1 py-1.5 text-[10px] font-bold text-white bg-red-600 hover:bg-red-700 rounded transition";
                btn.onclick = () => document.getElementById('revert-form').submit();
            } else if (action === 'logout') {
                title.innerText = "Exit Console?";
                desc.innerText = "Close session & return to noorgee.pk";
                btn.className = "flex-1 py-1.5 text-[10px] font-bold text-white bg-slate-800 hover:bg-black rounded transition";
                btn.onclick = () => document.getElementById('logout-form').submit();
            }

            // Reposition
            const rect = el.getBoundingClientRect();
            // If it's the logout button (navbar), align it differently
            if(action === 'logout') {
                popover.style.top = "45px";
                popover.style.right = "10px";
                popover.style.position = "fixed";
            } else {
                popover.style.top = "0px";
                popover.style.right = "0px";
                popover.style.position = "absolute";
            }

            popover.classList.remove('hidden');
        }

        function hideConfirm() {
            document.getElementById('confirm-popover').classList.add('hidden');
        }

        // Hide confirm on clicking outside
        document.addEventListener('click', function(event) {
            const popover = document.getElementById('confirm-popover');
            const forms = ['deploy-form', 'revert-form', 'logout-form'];
            let clickedInside = popover.contains(event.target);
            
            forms.forEach(id => {
                const f = document.getElementById(id);
                if(f && f.contains(event.target)) clickedInside = true;
            });

            if (!clickedInside) hideConfirm();
        });

        <?php if (!empty($logs)): ?>
            const allSuccess = <?php echo array_reduce($logs, fn($c, $l) => $c && $l['status'], true) ? 'true' : 'false'; ?>;
            if (allSuccess) {
                notify("Operation Successful");
            } else {
                notify("Errors Encountered", "error");
            }
        <?php endif; ?>
    </script>

</body>
</html>
