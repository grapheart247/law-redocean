<?php
/**
 * RedOcean Contact Form Handler
 * Reads from .env file securely
 */

header('Content-Type: application/json');

// --- 1. ENV PARSER FUNCTION ---
function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception('.env file missing');
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $env[trim($name)] = trim($value);
    }
    return $env;
}

try {
    // Load Credentials
    $env = loadEnv(__DIR__ . '/.env');
    $db_host = $env['DB_HOST'];
    $db_name = $env['DB_NAME'];
    $db_user = $env['DB_USER'];
    $db_pass = $env['DB_PASS'];

    // Connect
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) throw new Exception("DB Connection failed");
    $conn->set_charset("utf8mb4");

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database configuration error."]);
    exit;
}

// --- 2. FORM PROCESSING ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Inputs
    $site_source = filter_input(INPUT_POST, 'site_source', FILTER_SANITIZE_STRING) ?? 'lw.noorgee.pk';
    $name        = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email       = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone       = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $subject     = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
    $message     = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

    if (empty($name) || empty($email) || empty($message)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Please fill mandatory fields."]);
        exit;
    }

    // Insert
    $sql = "INSERT INTO messages (site_source, name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?, ?)";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $site_source, $name, $email, $phone, $subject, $message);
        
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Message saved successfully!"]);
        } else {
            throw new Exception("Insert failed");
        }
        $stmt->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to save message."]);
    }

} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed."]);
}
$conn->close();
?>
