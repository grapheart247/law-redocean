<?php
/**
 * RedOcean Contact Form Handler
 * Saves inquiries to the database.
 */

header('Content-Type: application/json');

// --- DATABASE CONFIGURATION ---
$db_host = 'localhost';
$db_name = 'noorgeec_pf';
$db_user = 'noorgeec_lw';
$db_pass = 'Pf_Lw_05-Feb';

// --- CONNECTION ---
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed."]);
    exit;
}

// --- FORM PROCESSING ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Sanitize and Validate Inputs
    $site_source = filter_input(INPUT_POST, 'site_source', FILTER_SANITIZE_STRING) ?? 'lw.noorgee.pk';
    $name        = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email       = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone       = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $subject     = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
    $message     = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

    // Basic Validation
    if (empty($name) || empty($email) || empty($message)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Please fill in all mandatory fields."]);
        exit;
    }

    // Insert into Database
    $sql = "INSERT INTO messages (site_source, name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?, ?)";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $site_source, $name, $email, $phone, $subject, $message);
        
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Message sent successfully!"]);
        } else {
            throw new Exception("Execution failed");
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
