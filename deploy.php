<?php
/**
 * RedOcean Deployment Console
 * ==========================================
 * A lightweight, single-file PHP tool to deploy from GitHub to cPanel
 * without requiring SSH access or rsync.
 * * Usage: Upload to public folder and access via browser:
 * https://yourdomain.com/deploy.php?key=YOUR_SECRET_KEY
 */

// ==========================================
// 1. CONFIGURATION
// ==========================================

// Full path to the repository (must contain .git folder)
$repo_path = '/home/noorgeec/repositories/law-redocean';

// Full path to the live public directory (where files should go)
$work_tree = '/home/noorgeec/noorgee.pk/Law';

// Target Git Branch
$branch = 'main-lw';

// Security Key (URL Parameter protection)
$secret_key = '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';

// GitHub Repository URL (For display purposes)
$github_url = 'https://github.com/grapheart247/law-redocean';


// ==========================================
// 2. SECURITY & HELPER FUNCTIONS
// ==========================================

// Protect the script
if (!isset($_GET['key']) || $_GET['key'] !== $secret_key) {
    header('HTTP/1.0 403 Forbidden');
    die('<body style="background:#0f172a;color:#ef4444;font-family:monospace;display:flex;height:100vh;justify-content:center;align-items:center;"><h1>[ACCESS DENIED] INVALID SECURITY KEY</h1></body>');
}

$action = isset($_POST['action']) ? $_POST['action'] : null;
$logs = [];

/**
 * Execute a shell command and return structured output
 */
function execute_command($cmd, $title) {
    $output = [];
    $return_var = 0;
    
    // Append 2>&1 to capture error output
    $full_cmd = "$cmd 2>&1";
    
    exec($full_cmd, $output, $return_var);
    
    return [
        'title'   => $title,
        'command' => $cmd,
        'output'  => implode("\n", $output),
        'status'  => $return_var === 0 // true if success
    ];
}

// ==========================================
// 3. ACTION LOGIC
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Ensure we are in the repo directory for git commands
    if (is_dir($repo_path)) {
        chdir($repo_path);
    } else {
        $logs[] = [
            'title' => 'Directory Check',
            'command' => "chdir($repo_path)",
            'output' => "Error: Repository directory does not exist.",
            'status' => false
        ];
    }

    if ($action === 'status') {
        // Just check status
        $logs[] = execute_command("git status", "Checking Repository Status");
        $logs[] = execute_command("git log -1 --format='%h - %s (%ci)'", "Latest Commit on Repo");
    } 
    elseif ($action === 'deploy') {
        // 1. Fetch latest changes
        $logs[] = execute_command("git fetch origin $branch", "Fetching from Origin");
        
        // 2. Hard Reset to match Origin (Cleans local repo state)
        $logs[] = execute_command("git reset --hard origin/$branch", "Resetting to origin/$branch");
        
        // 3. CRITICAL: Deploy to Live Directory using --work-tree
        // This command extracts the files from the repo logic to the public html folder
        $deploy_cmd = "git --git-dir=$repo_path/.git --work-tree=$work_tree checkout -f $branch";
        $logs[] = execute_command($deploy_cmd, "Deploying to Live Site");
        
        // 4. Check Status after deploy
        $logs[] = execute_command("git status", "Post-Deployment Status");
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RedOcean Deployer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Inter:wght@300;400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        
        /* Custom Scrollbar for Terminal */
        .terminal-scroll::-webkit-scrollbar { width: 8px; }
        .terminal-scroll::-webkit-scrollbar-track { background: #1e293b; }
        .terminal-scroll::-webkit-scrollbar-thumb { background: #475569; border-radius: 4px; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen">

    <!-- Navbar -->
    <nav class="bg-white border-b border-slate-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-slate-900 text-green-500 rounded flex items-center justify-center font-bold">
                        <i class="fa-solid fa-terminal"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-slate-900 tracking-tight">RedOcean <span class="text-slate-500 font-normal">Deployer</span></h1>
                        <p class="text-[10px] text-slate-400 font-mono uppercase tracking-wider">Branch: <?php echo htmlspecialchars($branch); ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <a href="<?php echo htmlspecialchars($github_url); ?>" target="_blank" class="text-slate-500 hover:text-slate-800 transition">
                        <i class="fa-brands fa-github text-xl"></i>
                    </a>
                    <a href="noorgee.pk/Law" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 text-sm font-medium transition">
                        <i class="fa-solid fa-arrow-left mr-1"></i> Back to Site
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        
        <!-- Info Bar -->
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-8 rounded-r-lg shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <p class="text-xs text-blue-600 font-bold uppercase mb-1">Configuration</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-1 text-sm">
                    <p><span class="text-slate-500">Repository:</span> <span class="font-mono text-slate-700"><?php echo htmlspecialchars($repo_path); ?></span></p>
                    <p><span class="text-slate-500">Live Target:</span> <span class="font-mono text-slate-700"><?php echo htmlspecialchars($work_tree); ?></span></p>
                </div>
            </div>
            <div class="flex-shrink-0">
                 <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <i class="fa-solid fa-shield-halved mr-1"></i> Secure Mode
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Controls Column -->
            <div class="lg:col-span-1 space-y-6">
                
                <!-- Deploy Card -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-slate-200">
                    <div class="p-6">
                        <div class="w-12 h-12 bg-red-50 text-red-600 rounded-lg flex items-center justify-center text-xl mb-4">
                            <i class="fa-solid fa-rocket"></i>
                        </div>
                        <h2 class="text-lg font-bold text-slate-900">Pull & Deploy</h2>
                        <p class="text-slate-500 text-sm mt-1 mb-6">Fetch changes from GitHub (<?php echo $branch; ?>) and overwrite the live site.</p>
                        
                        <form method="POST" onsubmit="return confirm('⚠️ WARNING: This will overwrite files in the live directory.\n\nAre you sure you want to deploy?');">
                            <input type="hidden" name="action" value="deploy">
                            <button type="submit" class="w-full py-3 px-4 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-lg transition flex items-center justify-center gap-2 shadow-md">
                                <span>Deploy Now</span>
                                <i class="fa-solid fa-bolt text-yellow-400"></i>
                            </button>
                        </form>
                    </div>
                    <div class="bg-slate-50 px-6 py-3 border-t border-slate-100">
                        <p class="text-xs text-slate-400 text-center">Resets local changes & updates file tree</p>
                    </div>
                </div>

                <!-- Status Card -->
                <div class="bg-white rounded-xl shadow border border-slate-200">
                    <div class="p-6 flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">System Status</h2>
                            <p class="text-slate-500 text-xs mt-1">Check git connectivity</p>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="action" value="status">
                            <button type="submit" class="px-4 py-2 bg-white border border-slate-300 text-slate-700 font-medium rounded-lg hover:bg-slate-50 transition text-sm">
                                Check Status
                            </button>
                        </form>
                    </div>
                </div>

            </div>

            <!-- Terminal Output Column -->
            <div class="lg:col-span-2">
                <div class="bg-[#0d1117] rounded-xl shadow-2xl overflow-hidden border border-slate-800 h-full min-h-[500px] flex flex-col">
                    
                    <!-- Terminal Header -->
                    <div class="bg-[#161b22] px-4 py-3 border-b border-slate-800 flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <div class="flex gap-1.5">
                                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                                <div class="w-3 h-3 rounded-full bg-green-500"></div>
                            </div>
                            <span class="ml-3 text-xs text-slate-400 font-mono">deployment-console -- <?php echo htmlspecialchars($branch); ?></span>
                        </div>
                        <span class="text-xs text-slate-600 font-mono"><?php echo date('H:i:s'); ?></span>
                    </div>

                    <!-- Terminal Body -->
                    <div class="p-6 font-mono text-sm overflow-y-auto flex-1 terminal-scroll">
                        
                        <?php if (empty($logs)): ?>
                            <div class="flex flex-col items-center justify-center h-full text-slate-600 space-y-4 opacity-50">
                                <i class="fa-solid fa-code-branch text-4xl"></i>
                                <p>Ready for command...</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <div class="mb-6 last:mb-0">
                                    <!-- Command Title -->
                                    <div class="flex items-center gap-2 mb-2">
                                        <?php if ($log['status']): ?>
                                            <span class="text-green-500 font-bold">[SUCCESS]</span>
                                        <?php else: ?>
                                            <span class="text-red-500 font-bold">[ERROR]</span>
                                        <?php endif; ?>
                                        <span class="text-slate-300"><?php echo htmlspecialchars($log['title']); ?></span>
                                    </div>
                                    
                                    <!-- The Command Executed -->
                                    <div class="text-blue-400 mb-2 opacity-80 pl-4 border-l-2 border-slate-700">
                                        $ <?php echo htmlspecialchars($log['command']); ?>
                                    </div>

                                    <!-- The Output -->
                                    <div class="text-slate-400 pl-4 whitespace-pre-wrap leading-relaxed"><?php 
                                        if (empty($log['output'])) {
                                            echo '<span class="italic opacity-50">(no output)</span>';
                                        } else {
                                            echo htmlspecialchars($log['output']);
                                        }
                                    ?></div>
                                </div>
                                <div class="h-px bg-slate-800 my-4 w-full"></div>
                            <?php endforeach; ?>
                            
                            <div class="mt-4 text-green-400 animate-pulse">
                                <span class="mr-2">➜</span> <span class="typing-cursor">_</span>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

        </div>
    </main>

</body>

</html>
