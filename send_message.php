<?php
/**
 * RedOcean Contact Form Handler
 * Reads from .env file securely
 */

header('Content-Type: application/json');

// --- 1. ENV PARSER FUNCTION ---
function loadEnv($path) {
    if (!file_exists($path)) {
        return null; // Return null so we can try multiple paths
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $env[trim($name)] = trim($value);
        }
    }
    return $env;
}

try {
    // Try to find the .env file in potential locations
    $pathsToTry = [
        __DIR__ . '/.env',           // Same folder
        __DIR__ . '/../.env',        // Parent folder
        __DIR__ . '/config/.env',    // Inside a config folder
    ];

    $env = null;
    $attemptedPaths = [];

    foreach ($pathsToTry as $path) {
        $attemptedPaths[] = $path;
        $env = loadEnv($path);
        if ($env !== null) break;
    }

    if ($env === null) {
        throw new Exception(".env file not found. Looked in: " . implode(", ", $attemptedPaths));
    }
    
    $db_host = $env['DB_HOST'] ?? 'localhost';
    $db_name = $env['DB_NAME'] ?? '';
    $db_user = $env['DB_USER'] ?? '';
    $db_pass = $env['DB_PASS'] ?? '';

    if (empty($db_name) || empty($db_user)) {
        throw new Exception("Database credentials in .env are empty.");
    }

    // Connect
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        throw new Exception("DB Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error", 
        "message" => "Configuration error: " . $e->getMessage()
    ]);
    exit;
}

// --- 2. FORM PROCESSING ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Inputs (Using '??' for safety)
    $site_source = $_POST['site_source'] ?? 'lw.noorgee.pk';
    $name        = $_POST['name'] ?? '';
    $email       = $_POST['email'] ?? '';
    $phone       = $_POST['phone'] ?? '';
    $subject     = $_POST['subject'] ?? '';
    $message     = $_POST['message'] ?? '';

    // Simple validation
    if (empty($name) || empty($email) || empty($message)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Please fill mandatory fields (Name, Email, Message)."]);
        exit;
    }

    // Insert - Matches your database columns: id, site_source, name, email, subject, message, created_at
    // (Note: phone is not in your column list but provided in form, adding it logic-wise or omitting based on your schema)
    $sql = "INSERT INTO messages (site_source, name, email, subject, message) VALUES (?, ?, ?, ?, ?)";
    
    try {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("sssss", $site_source, $name, $email, $subject, $message);
        
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Message saved successfully!"]);
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
    }

} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed."]);
}
$conn->close();
?>
