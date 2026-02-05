<?php
/**
 * RedOcean Admin Panel
 * View, Reply, and Manage Messages.
 * Security: Password Protected ('123')
 */

session_start();

// --- CONFIGURATION ---
$access_pass = '123';
$db_host = 'localhost';
$db_name = 'noorgeec_pf';
$db_user = 'noorgeec_lw';
$db_pass = 'Pf_Lw_05-Feb';

// --- ACCESS CONTROL ---
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
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login | RedOcean</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-slate-900 h-screen flex items-center justify-center p-4">
        <form method="POST" class="bg-slate-800 p-8 rounded-xl shadow-2xl w-full max-w-sm border border-slate-700">
            <h2 class="text-white text-xl font-bold mb-6 text-center">Admin Panel</h2>
            <input type="password" name="login_pass" placeholder="Enter Password" autofocus
                   class="w-full bg-slate-900 border border-slate-600 rounded-lg px-4 py-3 text-white mb-4 focus:border-lime-500 outline-none">
            <button type="submit" class="w-full bg-lime-600 hover:bg-lime-500 text-white font-bold py-3 rounded-lg transition">Login</button>
        </form>
    </body>
    </html>
    <?php
    exit;
}

// --- DATABASE CONNECTION ---
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

// --- HANDLE DELETE ---
if (isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    $conn->query("DELETE FROM messages WHERE id = $id");
    header("Location: admin.php");
    exit;
}

// --- FETCH MESSAGES ---
$result = $conn->query("SELECT * FROM messages ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | RedOcean Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col">

    <!-- Navbar -->
    <nav class="bg-slate-900 text-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 h-16 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-inbox text-lime-400"></i>
                <h1 class="font-bold text-lg">Message Center</h1>
            </div>
            <div class="flex items-center gap-4 text-sm">
                <a href="index.php" target="_blank" class="hover:text-lime-400"><i class="fa-solid fa-external-link-alt"></i> Site</a>
                <a href="?logout=true" class="bg-red-600 hover:bg-red-500 px-3 py-1.5 rounded transition">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-8 flex-1 w-full">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-100 text-slate-600 uppercase text-xs font-bold border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4">Source & Date</th>
                            <th class="px-6 py-4">Client Details</th>
                            <th class="px-6 py-4">Subject & Message</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-6 py-4 align-top whitespace-nowrap">
                                        <div class="flex flex-col gap-1">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-blue-100 text-blue-800 w-fit">
                                                <?php echo htmlspecialchars($row['site_source']); ?>
                                            </span>
                                            <span class="text-xs text-slate-500">
                                                <i class="fa-regular fa-clock mr-1"></i>
                                                <?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 align-top">
                                        <div class="font-bold text-slate-900"><?php echo htmlspecialchars($row['name']); ?></div>
                                        <div class="text-slate-500 text-xs mt-0.5"><i class="fa-regular fa-envelope mr-1"></i> <?php echo htmlspecialchars($row['email']); ?></div>
                                        <?php if(!empty($row['phone'])): ?>
                                            <div class="text-slate-500 text-xs mt-0.5"><i class="fa-solid fa-phone mr-1"></i> <?php echo htmlspecialchars($row['phone']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 align-top max-w-md">
                                        <div class="font-bold text-lime-700 mb-1"><?php echo htmlspecialchars($row['subject']); ?></div>
                                        <p class="text-slate-600 leading-relaxed text-xs line-clamp-3 hover:line-clamp-none transition-all duration-300">
                                            <?php echo nl2br(htmlspecialchars($row['message'])); ?>
                                        </p>
                                    </td>
                                    <td class="px-6 py-4 align-top text-right">
                                        <div class="flex justify-end gap-2">
                                            <a href="mailto:<?php echo htmlspecialchars($row['email']); ?>?subject=Re: <?php echo urlencode($row['subject']); ?>" 
                                               class="p-2 bg-blue-50 text-blue-600 rounded hover:bg-blue-600 hover:text-white transition" title="Reply">
                                                <i class="fa-solid fa-reply"></i>
                                            </a>
                                            <a href="mailto:?subject=Fwd: <?php echo urlencode($row['subject']); ?>&body=From: <?php echo urlencode($row['name']); ?>%0AEmail: <?php echo urlencode($row['email']); ?>%0A%0A<?php echo urlencode($row['message']); ?>" 
                                               class="p-2 bg-slate-100 text-slate-600 rounded hover:bg-slate-600 hover:text-white transition" title="Forward">
                                                <i class="fa-solid fa-share"></i>
                                            </a>
                                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this message?');" class="inline">
                                                <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" class="p-2 bg-red-50 text-red-600 rounded hover:bg-red-600 hover:text-white transition" title="Delete">
                                                    <i class="fa-regular fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-slate-400">
                                    <i class="fa-regular fa-folder-open text-4xl mb-3 block"></i>
                                    No messages found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-500 py-6 text-center text-xs border-t border-slate-800">
        <div class="flex justify-center gap-4 mb-2">
            <a href="https://noorgee.pk" class="hover:text-white">Main Page</a>
            <span>|</span>
            <a href="deploy.php" class="hover:text-white">Deploy Console</a>
        </div>
        <p>&copy; <?php echo date('Y'); ?> RedOcean Services.</p>
    </footer>

</body>
</html>
<?php $conn->close(); ?>
