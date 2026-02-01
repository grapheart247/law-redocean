<?php
/**
 * RedOcean Deployment Console (Compact Version)
 * ==========================================
 * Features: Deploy, Revert, Detailed Commit Info, Karachi Timezone.
 * Security: Password-based access ("123") with persistent sessions.
 * Fix: Post-Redirect-Get pattern to stop "Resubmit Form" popups.
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
    header("Location: deploy.php"); // Clean redirect after login
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
// 3. ACTION LOGIC (PRG PATTERN)
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

    // Store logs in session and redirect to avoid form resubmission popup
    $_SESSION['last_logs'] = $temp_logs;
    header("Location: deploy.php");
    exit;
}

// Retrieve logs from session
$logs = $_SESSION['last_logs'] ?? [];
unset($_SESSION['last_logs']); // Clear after showing once

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
        
        /* Bubble Message Animation */
        @keyframes slideIn { from { transform: translateY(100px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .bubble-msg { animation: slideIn 0.3s ease-out forwards; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 h-screen flex flex-col">

    <!-- Notification Container -->
    <div id="notification-area" class="fixed bottom-6 right-6 z-50 flex flex-col gap-3 pointer-events-none"></div>

    <!-- Confirmation Modal (Bubble Style) -->
    <div id="confirm-modal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[60] hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 p-6 max-w-sm w-full bubble-msg">
            <div class="flex items-center gap-4 mb-4 text-orange-600">
                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-triangle-exclamation text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900" id="confirm-title">Are you sure?</h3>
                    <p class="text-xs text-slate-500" id="confirm-desc">This will update live files.</p>
                </div>
            </div>
            <div class="flex gap-2">
                <button onclick="closeConfirm()" class="flex-1 py-2 text-xs font-bold text-slate-500 bg-slate-100 hover:bg-slate-200 rounded-lg transition">Cancel</button>
                <button id="confirm-btn" class="flex-1 py-2 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded-lg transition">Yes, Proceed</button>
            </div>
        </div>
    </div>

    <!-- Header -->
    <nav class="bg-slate-900 text-white shadow-lg flex-none">
        <div class="max-w-7xl mx-auto px-4 h-12 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-server text-green-400 text-sm"></i>
                <h1 class="font-bold text-sm tracking-tight uppercase">RedOcean Ops <span class="text-slate-500 font-normal">v2.3</span></h1>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-[10px] font-mono text-slate-400 uppercase tracking-widest hidden md:inline">Karachi: <?php echo date('H:i:s'); ?></span>
                <button onclick="location.reload()" class="p-1.5 hover:bg-slate-800 rounded transition text-slate-400" title="Refresh Page">
                    <i class="fa-solid fa-rotate text-xs"></i>
                </button>
                <form method="POST" id="logout-form" class="inline">
                    <input type="hidden" name="action" value="logout">
                    <button type="button" onclick="showBubbleConfirm('logout')" class="p-1.5 hover:text-red-400 transition text-slate-400" title="Logout & Exit">
                        <i class="fa-solid fa-right-from-bracket text-xs"></i>
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 py-4 flex-1 flex flex-col gap-4 overflow-hidden w-full">
        
        <!-- Top Row: Info & Controls -->
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

            <div class="md:col-span-4 flex flex-col gap-2">
                <div class="grid grid-cols-2 gap-2">
                    <form method="POST" id="deploy-form">
                        <input type="hidden" name="action" value="deploy">
                        <button type="button" onclick="showBubbleConfirm('deploy')" class="w-full h-full py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-bold rounded transition flex flex-col items-center justify-center gap-1">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            DEPLOY
                        </button>
                    </form>
                    <form method="POST" id="revert-form">
                        <input type="hidden" name="action" value="revert">
                        <button type="button" onclick="showBubbleConfirm('revert')" class="w-full h-full py-2 bg-white border border-red-200 text-red-600 hover:bg-red-50 text-xs font-bold rounded transition flex flex-col items-center justify-center gap-1">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            REVERT
                        </button>
                    </form>
                </div>
                <form method="POST" id="status-form">
                    <input type="hidden" name="action" value="status">
                    <button type="submit" class="w-full py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded transition text-[10px] uppercase">
                        <i class="fa-solid fa-magnifying-glass mr-1"></i> System Status Check
                    </button>
                </form>
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
        // Custom Bubble Notification System
        function notify(msg, type = 'success') {
            const area = document.getElementById('notification-area');
            const bubble = document.createElement('div');
            bubble.className = `bubble-msg pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg border ${type === 'success' ? 'bg-green-600 border-green-500 text-white' : 'bg-red-600 border-red-500 text-white'} min-w-[240px]`;
            
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            bubble.innerHTML = `<i class="fa-solid ${icon}"></i> <span class="text-xs font-bold uppercase tracking-wide">${msg}</span>`;
            
            area.appendChild(bubble);

            // Remove after 10 seconds
            setTimeout(() => {
                bubble.style.opacity = '0';
                bubble.style.transform = 'translateY(20px)';
                bubble.style.transition = 'all 0.5s ease';
                setTimeout(() => bubble.remove(), 500);
            }, 10000);
        }

        // Custom Confirmation Logic
        function showBubbleConfirm(action) {
            const modal = document.getElementById('confirm-modal');
            const btn = document.getElementById('confirm-btn');
            const title = document.getElementById('confirm-title');
            const desc = document.getElementById('confirm-desc');

            if (action === 'deploy') {
                title.innerText = "Confirm Deployment";
                desc.innerText = "Fetch latest code and update live site?";
                btn.onclick = () => document.getElementById('deploy-form').submit();
            } else if (action === 'revert') {
                title.innerText = "Confirm Revert";
                desc.innerText = "This will delete current changes and go back 1 step.";
                btn.onclick = () => document.getElementById('revert-form').submit();
            } else if (action === 'logout') {
                title.innerText = "Logging Out";
                desc.innerText = "Close deployment session and return to main site?";
                btn.onclick = () => document.getElementById('logout-form').submit();
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeConfirm() {
            const modal = document.getElementById('confirm-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // Show result notification if logs exist (on page load after redirect)
        <?php if (!empty($logs)): ?>
            const allSuccess = <?php echo array_reduce($logs, fn($c, $l) => $c && $l['status'], true) ? 'true' : 'false'; ?>;
            if (allSuccess) {
                notify("Operation completed successfully");
            } else {
                notify("Task finished with errors", "error");
            }
        <?php endif; ?>
    </script>

</body>
</html>
