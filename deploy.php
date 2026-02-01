<?php
/**
 * RedOcean Deployment Console (Compact Version)
 * ==========================================
 * Features: Deploy, Revert, Detailed Commit Info, Karachi Timezone.
 * Security: Session-based access (No long URL required after first login).
 */

session_start();
date_default_timezone_set('Asia/Karachi');

// ==========================================
// 1. CONFIGURATION
// ==========================================

$repo_path  = '/home/noorgeec/repositories/law-redocean';
$work_tree  = '/home/noorgeec/noorgee.pk/Law';
$branch     = 'main-lw';
$secret_key = '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'; // The Master Key
$github_url = 'https://github.com/grapheart247/law-redocean';

// ==========================================
// 2. ACCESS CONTROL (Auto-Session)
// ==========================================

// If key is provided in URL, save to session and redirect to clean URL
if (isset($_GET['key']) && $_GET['key'] === $secret_key) {
    $_SESSION['ro_authorized'] = true;
    header("Location: deploy.php");
    exit;
}

// Handle Login Form Submission
if (isset($_POST['login_key']) && $_POST['login_key'] === $secret_key) {
    $_SESSION['ro_authorized'] = true;
}

// Check Authorization
if (!isset($_SESSION['ro_authorized']) || $_SESSION['ro_authorized'] !== true) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>RedOcean | Auth Required</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-[#0f172a] h-screen flex items-center justify-center p-4">
        <form method="POST" class="bg-[#161b22] p-8 rounded-xl border border-slate-800 shadow-2xl w-full max-w-md">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-lock text-green-400 text-2xl"></i>
                </div>
                <h2 class="text-white font-bold text-xl">Deployment Auth</h2>
                <p class="text-slate-500 text-sm">Enter security key to access console</p>
            </div>
            <input type="password" name="login_key" placeholder="Enter GHP Key..." autofocus
                   class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white mb-4 focus:border-green-500 outline-none transition">
            <button type="submit" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-3 rounded-lg transition">
                Authorize Access
            </button>
        </form>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </body>
    </html>
    <?php
    exit;
}

// ==========================================
// 3. ACTION LOGIC
// ==========================================

$action = isset($_POST['action']) ? $_POST['action'] : null;
$logs = [];

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

function get_commit_details($path) {
    $cmd = "git -C $path log -1 --format='Hash: %h%nAuthor: %an%nDate: %ci%nSubject: %s%nDescription: %b'";
    $res = shell_exec($cmd);
    return $res ? htmlspecialchars($res) : "No commit data found.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (is_dir($repo_path)) chdir($repo_path);

    if ($action === 'status') {
        $logs[] = execute_command("git status", "System Check");
    } 
    elseif ($action === 'deploy') {
        $logs[] = execute_command("git fetch origin $branch", "Fetching Updates");
        $logs[] = execute_command("git reset --hard origin/$branch", "Syncing with GitHub");
        $deploy_cmd = "git --git-dir=$repo_path/.git --work-tree=$work_tree checkout -f $branch";
        $logs[] = execute_command($deploy_cmd, "Live Extraction");
    }
    elseif ($action === 'revert') {
        $logs[] = execute_command("git reset --hard HEAD~1", "Rolling Back 1 Commit");
        $deploy_cmd = "git --git-dir=$repo_path/.git --work-tree=$work_tree checkout -f $branch";
        $logs[] = execute_command($deploy_cmd, "Restoring Previous Files to Live");
    }
    elseif ($action === 'logout') {
        session_destroy();
        header("Location: deploy.php");
        exit;
    }
}

$current_commit = get_commit_details($repo_path);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RedOcean | Compact Ops</title>
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
    </style>
</head>
<body class="bg-slate-50 text-slate-800 h-screen flex flex-col">

    <!-- Header -->
    <nav class="bg-slate-900 text-white shadow-lg flex-none">
        <div class="max-w-7xl mx-auto px-4 h-12 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-server text-green-400 text-sm"></i>
                <h1 class="font-bold text-sm tracking-tight uppercase">RedOcean Ops <span class="text-slate-500 font-normal">v2.1</span></h1>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-[10px] font-mono text-slate-400 uppercase tracking-widest hidden md:inline">Karachi: <?php echo date('H:i:s'); ?></span>
                <button onclick="location.reload()" class="p-1.5 hover:bg-slate-800 rounded transition text-slate-400" title="Refresh Page">
                    <i class="fa-solid fa-rotate text-xs"></i>
                </button>
                <form method="POST" class="inline">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="p-1.5 hover:text-red-400 transition text-slate-400" title="Logout">
                        <i class="fa-solid fa-right-from-bracket text-xs"></i>
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 py-4 flex-1 flex flex-col gap-4 overflow-hidden w-full">
        
        <!-- Top Row: Info & Controls -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 flex-none">
            
            <!-- Commit Details (Compact) -->
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

            <!-- Action Buttons (Row) -->
            <div class="md:col-span-4 flex flex-col gap-2">
                <div class="grid grid-cols-2 gap-2">
                    <form method="POST" onsubmit="return confirm('Deploy latest changes?');">
                        <input type="hidden" name="action" value="deploy">
                        <button type="submit" class="w-full h-full py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-bold rounded transition flex flex-col items-center justify-center gap-1">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            DEPLOY
                        </button>
                    </form>
                    <form method="POST" onsubmit="return confirm('⚠️ DANGER: Revert to previous version?');">
                        <input type="hidden" name="action" value="revert">
                        <button type="submit" class="w-full h-full py-2 bg-white border border-red-200 text-red-600 hover:bg-red-50 text-xs font-bold rounded transition flex flex-col items-center justify-center gap-1">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            REVERT
                        </button>
                    </form>
                </div>
                <form method="POST">
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
        <p>Repo: <?php echo $repo_path; ?></p>
        <p>&copy; RedOcean Services - Session Active</p>
    </footer>

</body>
</html>
