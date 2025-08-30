<?php
// Standalone get_customer_count.php to work around InfinityFree routing issues
// Force CORS headers to be set for ALL requests
if (!headers_sent()) {
    // Always set CORS headers regardless of origin
    header('Access-Control-Allow-Origin: https://autowash-hub.vercel.app');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
    
    // Debug logging
    error_log("CORS Headers Set for get_customer_count.php - Origin: " . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'none'));
}

// Handle OPTIONS preflight request FIRST - before any other processing
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    error_log("Handling OPTIONS preflight request for get_customer_count.php");
    
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

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

// Include required files
require_once "./config/env.php";
require_once "./autoload.php";
require_once "./modules/get.php";
require_once "./config/database.php";

try {
    // Create database connection
    $connection = new Connection();
    $pdo = $connection->connect();
    
    // Initialize Get module
    $get = new Get($pdo);
    
    // Call get_customer_count method
    $result = $get->get_customer_count();
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Get customer count error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
