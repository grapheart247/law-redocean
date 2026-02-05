<?php
/**
 * RedOcean Admin Panel
 * Uses .env for credentials
 */

session_start();

// --- 1. ENV PARSER ---
function loadEnv($path) {
    if (!file_exists($path)) return [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $env[trim($name)] = trim($value);
    }
    return $env;
}

$env = loadEnv(__DIR__ . '/.env');
$access_pass = '123'; // Admin Password

// --- 2. ACCESS CONTROL ---
if (isset($_POST['login_pass']) && $_POST['login_pass'] === $access_pass) {
    $_SESSION['admin_authorized'] = true;
    header("Location: admin.php");
    exit;
}
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}
if (!isset($_SESSION['admin_authorized']) || $_SESSION['admin_authorized'] !== true) {
    echo '<!DOCTYPE html><html lang="en"><head><title>Admin Login</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-slate-900 h-screen flex items-center justify-center"><form method="POST" class="bg-slate-800 p-8 rounded-xl shadow-lg w-full max-w-sm"><h2 class="text-white text-xl font-bold mb-6 text-center">Admin Access</h2><input type="password" name="login_pass" placeholder="Password" class="w-full p-3 rounded mb-4" autofocus><button type="submit" class="w-full bg-lime-600 text-white font-bold py-3 rounded">Login</button></form></body></html>';
    exit;
}

// --- 3. DATABASE ---
$conn = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'], $env['DB_NAME']);
if ($conn->connect_error) die("DB Connection Failed");
$conn->set_charset("utf8mb4");

// Delete Logic
if (isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    $conn->query("DELETE FROM messages WHERE id = $id");
    header("Location: admin.php");
    exit;
}

$result = $conn->query("SELECT * FROM messages ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen p-8">
    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Inbox</h1>
            <a href="?logout=true" class="bg-red-600 text-white px-4 py-2 rounded">Logout</a>
        </div>
        
        <div class="bg-white rounded-xl shadow border border-slate-200 overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-100 border-b">
                    <tr>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3">From</th>
                        <th class="px-6 py-3">Message</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">
                            <?php echo date('M d, Y', strtotime($row['created_at'])); ?><br>
                            <?php echo date('h:i A', strtotime($row['created_at'])); ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold"><?php echo htmlspecialchars($row['name']); ?></div>
                            <div class="text-xs text-slate-500"><?php echo htmlspecialchars($row['email']); ?></div>
                            <div class="text-xs text-slate-500"><?php echo htmlspecialchars($row['phone']); ?></div>
                            <div class="mt-1 text-[10px] bg-blue-100 text-blue-800 px-1 rounded w-fit"><?php echo htmlspecialchars($row['site_source']); ?></div>
                        </td>
                        <td class="px-6 py-4 max-w-md">
                            <div class="font-bold text-lime-700 mb-1"><?php echo htmlspecialchars($row['subject']); ?></div>
                            <p class="text-slate-600 line-clamp-3"><?php echo nl2br(htmlspecialchars($row['message'])); ?></p>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <form method="POST" onsubmit="return confirm('Delete?');">
                                <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                <button class="text-red-500 hover:text-red-700"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
