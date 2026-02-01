<?php
/**
 * RedOcean Deployment Console (Advanced)
 * ==========================================
 * Features: Deploy, Revert, Detailed Commit Info, Karachi Timezone.
 */

// ==========================================
// 1. CONFIGURATION
// ==========================================

date_default_timezone_set('Asia/Karachi');

// Full path to the repository (must contain .git folder)
$repo_path = '/home/noorgeec/repositories/law-redocean';

// Full path to the live public directory (where files should go)
$work_tree = '/home/noorgeec/noorgee.pk/Law';

// Target Git Branch
$branch = 'main-lw';

// Security Key (URL Parameter protection)
$secret_key = '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';

// GitHub Repository URL
$github_url = 'https://github.com/grapheart247/law-redocean';

// ==========================================
// 2. SECURITY & HELPER FUNCTIONS
// ==========================================

if (!isset($_GET['key']) || $_GET['key'] !== $secret_key) {
    header('HTTP/1.0 403 Forbidden');
    die('<body style="background:#0f172a;color:#ef4444;font-family:monospace;display:flex;height:100vh;justify-content:center;align-items:center;"><h1>[ACCESS DENIED] INVALID SECURITY KEY</h1></body>');
}

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
    // Custom format for Karachi Time and Extended Description
    // %h: short hash, %an: author name, %ci: committer date, %s: subject, %b: body
    $cmd = "git -C $path log -1 --format='Hash: %h%nAuthor: %an%nDate: %ci%nSubject: %s%nDescription: %b'";
    $res = shell_exec($cmd);
    return $res ? htmlspecialchars($res) : "No commit data found.";
}

// ==========================================
// 3. ACTION LOGIC
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (is_dir($repo_path)) {
        chdir($repo_path);
    }

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
        // Revert local repo back one step and force checkout to work tree
        $logs[] = execute_command("git reset --hard HEAD~1", "Rolling Back 1 Commit");
        $deploy_cmd = "git --git-dir=$repo_path/.git --work-tree=$work_tree checkout -f $branch";
        $logs[] = execute_command($deploy_cmd, "Restoring Previous Files to Live");
    }
}

$current_commit = get_commit_details($repo_path);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RedOcean | Advanced Deployer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Inter:wght@300;400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; white-space: pre-wrap; }
        .terminal-bg { background: #0d1117; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen">

    <nav class="bg-slate-900 text-white shadow-xl">
        <div class="max-w-7xl mx-auto px-4 h-16 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-server text-green-400"></i>
                <h1 class="font-bold tracking-tight">RedOcean <span class="text-slate-400 font-normal">Ops</span></h1>
            </div>
            <div class="flex gap-4">
                <button onclick="location.reload()" class="p-2 hover:bg-slate-800 rounded-lg transition" title="Refresh Page">
                    <i class="fa-solid fa-rotate"></i>
                </button>
                <a href="<?php echo $github_url; ?>" target="_blank" class="p-2 hover:bg-slate-800 rounded-lg transition">
                    <i class="fa-brands fa-github text-xl"></i>
                </a>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Sidebar Controls -->
            <div class="space-y-6">
                <!-- Commit Info Card -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="bg-slate-50 px-4 py-3 border-b border-slate-200 flex justify-between items-center">
                        <span class="text-xs font-bold uppercase text-slate-500">Active Commit</span>
                        <span class="text-[10px] bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-bold">LIVE</span>
                    </div>
                    <div class="p-4">
                        <div class="font-mono text-[11px] text-slate-600 leading-relaxed bg-slate-50 p-3 rounded-lg border border-slate-100">
                            <?php echo $current_commit; ?>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                    <form method="POST" onsubmit="return confirm('Deploy latest changes from GitHub?');">
                        <input type="hidden" name="action" value="deploy">
                        <button type="submit" class="w-full py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg transition flex items-center justify-center gap-2">
                            <i class="fa-solid fa-cloud-arrow-up"></i> Push Deployment
                        </button>
                    </form>

                    <form method="POST" onsubmit="return confirm('⚠️ DANGER: Revert to the previous commit? This will downgrade your live site files.');">
                        <input type="hidden" name="action" value="revert">
                        <button type="submit" class="w-full py-3 bg-white border border-red-200 text-red-600 hover:bg-red-50 font-bold rounded-lg transition flex items-center justify-center gap-2">
                            <i class="fa-solid fa-clock-rotate-left"></i> Revert Version
                        </button>
                    </form>

                    <form method="POST">
                        <input type="hidden" name="action" value="status">
                        <button type="submit" class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg transition text-sm">
                            <i class="fa-solid fa-magnifying-glass mr-2"></i> System Check
                        </button>
                    </form>
                </div>
            </div>

            <!-- Terminal Output -->
            <div class="lg:col-span-2">
                <div class="terminal-bg rounded-xl shadow-2xl border border-slate-800 flex flex-col h-[600px]">
                    <div class="bg-[#161b22] px-4 py-2 border-b border-slate-800 flex items-center justify-between">
                        <div class="flex gap-1.5">
                            <div class="w-2.5 h-2.5 rounded-full bg-red-500"></div>
                            <div class="w-2.5 h-2.5 rounded-full bg-yellow-500"></div>
                            <div class="w-2.5 h-2.5 rounded-full bg-green-500"></div>
                        </div>
                        <span class="text-[10px] font-mono text-slate-500 uppercase tracking-tighter">Karachi Time: <?php echo date('d-M-Y H:i:s'); ?></span>
                    </div>
                    
                    <div class="p-6 overflow-y-auto flex-1 font-mono text-xs">
                        <?php if (empty($logs)): ?>
                            <div class="h-full flex flex-col items-center justify-center text-slate-700">
                                <i class="fa-solid fa-terminal text-4xl mb-3 opacity-20"></i>
                                <p class="opacity-40">Awaiting user action...</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <div class="mb-6">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="<?php echo $log['status'] ? 'text-green-500' : 'text-red-500'; ?> font-bold">
                                            [<?php echo $log['status'] ? 'OK' : 'FAIL'; ?>]
                                        </span>
                                        <span class="text-slate-400"><?php echo $log['title']; ?></span>
                                    </div>
                                    <div class="text-blue-400 mb-1 opacity-70">$ <?php echo $log['command']; ?></div>
                                    <div class="text-slate-300 pl-4 border-l border-slate-800"><?php echo nl2br(htmlspecialchars($log['output'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </main>
</body>
</html>
