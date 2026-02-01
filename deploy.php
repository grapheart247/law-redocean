<?php
/**
 * RedOcean Services - Git Deployment Tool
 * Designed for cPanel environments without SSH/Rsync access.
 */

// SECURITY: Validate the key before proceeding
$secret_key = "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"; // Example key from requirements
if (!isset($_GET['key']) || $_GET['key'] !== $secret_key) {
    die("Unauthorized Access: Invalid or missing security key.");
}

// CONFIGURATION
$repo_path = "/home/noorgeec/repositories/tax-repo"; // Path to .git folder
$work_tree = "/home/noorgeec/tx.noorgee.pk";       // Path to live files
$branch = "main";
$github_url = "https://github.com/grapheart247/tax-repo/";

$output = [];
$action_taken = false;

/**
 * Helper to calculate time elapsed
 */
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

// HANDLE ACTIONS
if (isset($_POST['action'])) {
    $action_taken = true;
    if ($_POST['action'] === 'deploy') {
        $commands = [
            "cd $repo_path && git fetch origin $branch 2>&1",
            "cd $repo_path && git reset --hard origin/$branch 2>&1",
            "git --git-dir=$repo_path/.git --work-tree=$work_tree checkout -f $branch . 2>&1"
        ];
        foreach ($commands as $cmd) {
            $res = shell_exec($cmd);
            $output[] = [
                'cmd' => $cmd,
                'res' => $res ? trim($res) : "Done (No output)"
            ];
        }
    } elseif ($_POST['action'] === 'status') {
        $cmd = "cd $repo_path && git status 2>&1";
        $output[] = [
            'cmd' => $cmd,
            'res' => shell_exec($cmd)
        ];
    }
}

// GET LAST COMMIT INFO
$last_commit_date = shell_exec("cd $repo_path && git log -1 --format=%cd --date=iso 2>&1");
$time_ago = "";
if ($last_commit_date && strpos($last_commit_date, 'fatal') === false) {
    $time_ago = time_elapsed_string(trim($last_commit_date));
} else {
    $last_commit_date = "No commit info available.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RedOcean | Deployment Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono&family=Inter:wght@400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .terminal { font-family: 'JetBrains Mono', monospace; background: #0d1117; color: #c9d1d9; }
        .cmd-text { color: #58a6ff; }
        .res-text { color: #8b949e; }
        .success-tag { color: #3fb950; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800">

    <!-- Header -->
    <header class="bg-slate-900 text-white py-6 shadow-xl">
        <div class="max-w-6xl mx-auto px-4 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-lime-600 rounded-lg flex items-center justify-center text-2xl">
                    <i class="fa-solid fa-server"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-tight uppercase">Infrastructure Manager</h1>
                    <p class="text-xs text-slate-400 font-mono"><?php echo htmlspecialchars($work_tree); ?></p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <a href="<?php echo $github_url; ?>" target="_blank" class="text-slate-400 hover:text-white transition flex items-center gap-2 text-sm">
                    <i class="fa-brands fa-github text-lg"></i> Repository
                </a>
                <a href="index.php" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded text-sm font-semibold transition border border-slate-700">
                    Back to Site
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-10">
        
        <!-- Status Card -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-8 flex flex-col md:flex-row justify-between items-center gap-6">
            <div>
                <h2 class="text-slate-500 text-xs font-bold uppercase tracking-widest mb-1">Latest Commit Status</h2>
                <div class="flex items-baseline gap-3">
                    <p class="text-lg font-bold text-slate-800">Date: <?php echo htmlspecialchars(trim($last_commit_date)); ?></p>
                    <?php if($time_ago): ?>
                        <span class="px-3 py-1 bg-lime-100 text-lime-700 text-xs font-bold rounded-full">
                            <i class="fa-solid fa-clock mr-1"></i> <?php echo $time_ago; ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex gap-3">
                <form method="POST" onsubmit="return confirm('Initiate Git Pull & Hard Reset? This will overwrite live changes.');">
                    <input type="hidden" name="action" value="deploy">
                    <button type="submit" class="bg-lime-600 hover:bg-lime-700 text-white px-6 py-3 rounded-lg font-bold shadow-lg transition transform hover:-translate-y-1 flex items-center gap-2">
                        <i class="fa-solid fa-cloud-arrow-down"></i> Pull & Deploy
                    </button>
                </form>
                <form method="POST">
                    <input type="hidden" name="action" value="status">
                    <button type="submit" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-6 py-3 rounded-lg font-bold transition flex items-center gap-2 border border-slate-200">
                        <i class="fa-solid fa-magnifying-glass"></i> Check Status
                    </button>
                </form>
            </div>
        </div>

        <!-- Terminal Output -->
        <div class="rounded-xl overflow-hidden shadow-2xl">
            <div class="bg-slate-800 px-4 py-2 flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                <div class="w-3 h-3 rounded-full bg-green-500"></div>
                <span class="ml-2 text-slate-400 text-xs font-mono uppercase tracking-widest">Deployment Terminal</span>
            </div>
            <div class="terminal p-6 h-[500px] overflow-y-auto text-sm leading-relaxed">
                <?php if (!$action_taken): ?>
                    <p class="text-slate-500 italic">Waiting for command... Select an action above to view terminal history.</p>
                <?php else: ?>
                    <?php foreach ($output as $entry): ?>
                        <div class="mb-6">
                            <p class="cmd-text mb-1"><span class="text-slate-600">$</span> <?php echo htmlspecialchars($entry['cmd']); ?></p>
                            <pre class="res-text whitespace-pre-wrap pl-4 border-l border-slate-700 ml-1"><?php echo htmlspecialchars($entry['res']); ?></pre>
                            <p class="text-[10px] mt-1 success-tag font-bold uppercase tracking-widest">[Success]</p>
                        </div>
                    <?php endforeach; ?>
                    <div class="pt-4 text-lime-500 font-bold">
                        &gt; Process complete.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="py-10 text-center text-slate-400 text-xs">
        <p>RedOcean Infrastructure Tool &bull; Git Version Management &bull; Built for NoorGee Enterprise</p>
    </footer>

</body>
</html>
