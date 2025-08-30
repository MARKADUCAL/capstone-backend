<?php
// Standalone add_customer.php to work around InfinityFree routing issues
// Force CORS headers to be set for ALL requests
if (!headers_sent()) {
    // Always set CORS headers regardless of origin
    header('Access-Control-Allow-Origin: https://autowash-hub.vercel.app');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
    
    // Debug logging
    error_log("CORS Headers Set for add_customer.php - Origin: " . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'none'));
}

// Handle OPTIONS preflight request FIRST - before any other processing
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    error_log("Handling OPTIONS preflight request for add_customer.php");
    
    // Set CORS headers again to ensure they're applied
    header('Access-Control-Allow-Origin: https://autowash-hub.vercel.app');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
    
    // Return 200 OK for preflight
    http_response_code(200);
    echo json_encode(['status' => 'preflight_ok']);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

// Include required files
require_once "./config/env.php";
require_once "./autoload.php";
require_once "./modules/post.php";
require_once "./config/database.php";

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input);
    
    if (!$data) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    if (!isset($data->first_name) || !isset($data->last_name) || !isset($data->email) || !isset($data->password)) {
        throw new Exception('First name, last name, email, and password are required');
    }
    
    // Create database connection
    $connection = new Connection();
    $pdo = $connection->connect();
    
    // Initialize Post module
    $post = new Post($pdo);
    
    // Call add_customer method
    $result = $post->add_customer($data);
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Add customer error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
