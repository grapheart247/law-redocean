<?php
session_start();

// --- SECURE CREDENTIALS LOADING ---
$env_path = '/home/noorgeec/noorgee.pk/Law/.gitignore/ng-lw.env';
$env = [];

if (file_exists($env_path)) {
    $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $env[trim($name)] = trim(trim($value), "\"'");
    }
}

// Config
$host = $env['DB_HOST'] ?? 'localhost';
$dbname = $env['DB_NAME'] ?? 'noorgeec_pf';
$dbuser = $env['DB_USER'] ?? '';
$dbpass = $env['DB_PASS'] ?? '';

// Authentication
if (isset($_POST['password'])) {
    if ($_POST['password'] === '123') { 
        $_SESSION['admin_auth'] = true;
    } else {
        $error = "Invalid Password";
    }
}
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}
if (!isset($_SESSION['admin_auth'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head><title>Admin Login</title><script src="https://cdn.tailwindcss.com"></script></head>
    <body class="bg-slate-900 flex items-center justify-center h-screen">
        <form method="POST" class="bg-white p-8 rounded-lg shadow-xl text-center w-80">
            <h2 class="text-2xl font-bold mb-6 text-slate-800">Admin Login</h2>
            <?php if(isset($error)) echo "<p class='text-red-500 text-sm mb-4'>$error</p>"; ?>
            <input type="password" name="password" placeholder="Enter PIN" class="border p-3 rounded w-full mb-4 focus:outline-none focus:ring-2 focus:ring-red-500">
            <button class="bg-red-700 hover:bg-red-800 text-white font-bold py-2 rounded w-full transition">LOGIN</button>
        </form>
    </body>
    </html>
    <?php
    exit;
}

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $dbuser, $dbpass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Handle Bulk Delete
    if (isset($_POST['bulk_delete']) && !empty($_POST['selected_ids'])) {
        $ids = implode(',', array_map('intval', $_POST['selected_ids']));
        $conn->exec("DELETE FROM messages WHERE id IN ($ids)");
    }

    // Handle Single Delete
    if (isset($_POST['delete_id'])) {
        $stmt = $conn->prepare("DELETE FROM messages WHERE id = :id");
        $stmt->bindParam(':id', $_POST['delete_id']);
        $stmt->execute();
    }

    // Get Unique Sources for Dropdown
    $sources_stmt = $conn->query("SELECT DISTINCT site_source FROM messages");
    $sources = $sources_stmt->fetchAll(PDO::FETCH_COLUMN);

    // Filter Logic (Default to lw.noorgee.pk)
    $filter_source = $_GET['source'] ?? 'lw.noorgee.pk';
    
    $query = "SELECT * FROM messages";
    $params = [];
    
    if ($filter_source !== 'ALL') {
        $query .= " WHERE site_source = :source";
        $params[':source'] = $filter_source;
    }
    
    $query .= " ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("DB Connection Error. Check credentials.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel | RedOcean</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen">

    <!-- Navbar -->
    <nav class="bg-slate-900 text-white shadow-lg sticky top-0 z-50">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-4">
                    <span class="font-bold text-xl tracking-tight"><i class="fa-solid fa-shield-halved text-red-500 mr-2"></i>Admin Panel</span>
                </div>
                <div class="flex items-center gap-3">
                    <a href="index.html" target="_blank" class="bg-slate-700 hover:bg-slate-600 px-3 py-1.5 rounded text-sm transition flex items-center gap-2">
                        <i class="fa-solid fa-house"></i> Home
                    </a>
                    <a href="?logout=true" class="bg-red-600 hover:bg-red-700 px-3 py-1.5 rounded text-sm transition flex items-center gap-2">
                        <i class="fa-solid fa-power-off"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Toolbar -->
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <form method="GET" class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <label class="font-bold text-slate-600 text-sm">Filter Source:</label>
                <select name="source" onchange="this.form.submit()" class="border border-slate-300 rounded p-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="ALL" <?php echo $filter_source === 'ALL' ? 'selected' : ''; ?>>-- Show All --</option>
                    <?php foreach ($sources as $src): ?>
                        <option value="<?php echo htmlspecialchars($src); ?>" <?php echo $filter_source === $src ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($src); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="text-sm text-slate-500">
                Total Messages: <span class="font-bold text-slate-800"><?php echo count($messages); ?></span>
            </div>
        </form>

        <!-- Messages Table Form (for bulk actions) -->
        <form method="POST" id="bulkForm">
            <div class="bg-white rounded-lg shadow border border-slate-200 overflow-hidden">
                <div class="p-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                    <h2 class="font-bold text-slate-700">Inbox</h2>
                    <button type="submit" name="bulk_delete" onclick="return confirm('Delete selected messages?')" class="bg-red-100 text-red-700 hover:bg-red-200 px-3 py-1 rounded text-xs font-bold uppercase transition">
                        <i class="fa-solid fa-trash mr-1"></i> Delete Selected
                    </button>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-slate-500 uppercase bg-slate-100 border-b">
                            <tr>
                                <th class="p-4 w-4">
                                    <input type="checkbox" id="selectAll" class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                                </th>
                                <th class="px-4 py-3">Source & Date</th>
                                <th class="px-4 py-3">Sender Info</th>
                                <th class="px-4 py-3">Subject</th>
                                <th class="px-4 py-3">Message</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <?php if (count($messages) > 0): ?>
                                <?php foreach ($messages as $msg): ?>
                                <tr class="hover:bg-slate-50 transition group">
                                    <td class="p-4 w-4">
                                        <input type="checkbox" name="selected_ids[]" value="<?php echo $msg['id']; ?>" class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="bg-blue-100 text-blue-800 text-[10px] font-bold px-2 py-0.5 rounded border border-blue-200"><?php echo htmlspecialchars($msg['site_source']); ?></span>
                                        <div class="text-xs text-slate-400 mt-1 font-mono"><?php echo date('M d, Y', strtotime($msg['created_at'])); ?><br><?php echo date('h:i A', strtotime($msg['created_at'])); ?></div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-bold text-slate-800"><?php echo htmlspecialchars($msg['name']); ?></div>
                                        <a href="mailto:<?php echo $msg['email']; ?>" class="text-blue-600 hover:underline text-xs"><?php echo htmlspecialchars($msg['email']); ?></a>
                                    </td>
                                    <td class="px-4 py-4 font-medium text-slate-700">
                                        <?php echo htmlspecialchars($msg['subject']); ?>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="max-w-xs truncate text-slate-500" title="<?php echo htmlspecialchars($msg['message']); ?>">
                                            <?php echo htmlspecialchars($msg['message']); ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <!-- Reply -->
                                            <a href="mailto:<?php echo $msg['email']; ?>?subject=Re: <?php echo rawurlencode($msg['subject']); ?>" 
                                               class="bg-green-50 text-green-600 border border-green-200 hover:bg-green-600 hover:text-white p-2 rounded transition" title="Reply">
                                                <i class="fa-solid fa-reply"></i>
                                            </a>
                                            <!-- Forward -->
                                            <a href="mailto:?subject=Fwd: <?php echo rawurlencode($msg['subject']); ?>&body=From: <?php echo rawurlencode($msg['name']); ?>%0AEmail: <?php echo rawurlencode($msg['email']); ?>%0A%0A<?php echo rawurlencode($msg['message']); ?>" 
                                               class="bg-blue-50 text-blue-600 border border-blue-200 hover:bg-blue-600 hover:text-white p-2 rounded transition" title="Forward">
                                                <i class="fa-solid fa-share"></i>
                                            </a>
                                            <!-- Single Delete -->
                                            <button type="submit" name="delete_id" value="<?php echo $msg['id']; ?>" onclick="return confirm('Delete this specific message?')" 
                                                    class="bg-red-50 text-red-600 border border-red-200 hover:bg-red-600 hover:text-white p-2 rounded transition" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-500 italic">
                                        No messages found for source: <b><?php echo htmlspecialchars($filter_source); ?></b>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>

    <script>
        // Select All Logic
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="selected_ids[]"]');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    </script>
</body>
</html>
