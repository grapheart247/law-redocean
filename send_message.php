<?php
header('Content-Type: application/json');

// --- SECURE CREDENTIALS LOADING ---
// Path to your hidden environment file
$env_path = '/home/noorgeec/noorgee.pk/Law/.gitignore/ng-lw.env';
$env = [];

if (file_exists($env_path)) {
    $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments and lines without =
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        
        list($name, $value) = explode('=', $line, 2);
        $env[trim($name)] = trim(trim($value), "\"'");
    }
}

// Database Credentials (loaded from env with fallbacks)
$host = $env['DB_HOST'] ?? 'localhost';
$dbname = $env['DB_NAME'] ?? 'noorgeec_pf'; // Defaulting to known DB if missing in env
$username = $env['DB_USER'] ?? '';
$password = $env['DB_PASS'] ?? '';

try {
    if (empty($username) || empty($password)) {
        throw new PDOException("Database credentials not found in environment file.");
    }

    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        // Sanitize and validate
        $site_source = htmlspecialchars(strip_tags($_POST['site_source'] ?? 'lw.noorgee.pk'));
        $name = htmlspecialchars(strip_tags($_POST['name']));
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $phone = htmlspecialchars(strip_tags($_POST['phone']));
        $subject = htmlspecialchars(strip_tags($_POST['subject']));
        $message = htmlspecialchars(strip_tags($_POST['message']));

        if (!$name || !$email || !$message) {
            echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
            exit;
        }

        // Insert into database
        // Appending phone to message as a backup if column doesn't exist, 
        // though standard practice is to use specific columns if available.
        $final_message = "Phone: " . $phone . "\n\n" . $message;

        $stmt = $conn->prepare("INSERT INTO messages (site_source, name, email, subject, message, created_at) VALUES (:site_source, :name, :email, :subject, :message, NOW())");
        
        $stmt->bindParam(':site_source', $site_source);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':subject', $subject);
        $stmt->bindParam(':message', $final_message);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Message sent successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        }
    }
} catch(PDOException $e) {
    // In production, do not echo $e->getMessage() directly to avoid leaking paths
    echo json_encode(['status' => 'error', 'message' => 'Connection failed. Please try again later.']);
}
?>
