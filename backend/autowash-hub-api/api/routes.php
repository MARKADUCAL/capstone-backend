<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "./config/env.php";
loadEnv(__DIR__ . '/.env');

// Include required modules
require_once "./autoload.php";
require_once "./modules/get.php";
require_once "./modules/post.php";
require_once "./modules/put.php";
require_once "./config/database.php";

// CORS headers
header('Access-Control-Allow-Origin: http://localhost:4200');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Get the request method and endpoint
$method = $_SERVER['REQUEST_METHOD'];
$request = $_SERVER['REQUEST_URI'];

// Handle rewritten URLs from .htaccess
if (isset($_GET['request'])) {
    $request = '/' . $_GET['request'];
}

// Create database connection
$connection = new Connection();
$pdo = $connection->connect();

// Initialize modules
$post = new Post($pdo);
$get = new Get($pdo);
$put = new Put($pdo);

// Handle OPTIONS request (CORS preflight)
if ($method === 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit();
}

// Handle GET requests
if ($method === 'GET') {
    if (strpos($request, 'get_customer_count') !== false) {
        $result = $get->get_customer_count();
        echo json_encode($result);
        exit();
    }
    
    if (strpos($request, 'get_employee_count') !== false) {
        $result = $get->get_employee_count();
        echo json_encode($result);
        exit();
    }
    
    if (strpos($request, 'get_all_customers') !== false) {
        $result = $get->get_all_customers();
        echo json_encode($result);
        exit();
    }
    
    if (strpos($request, 'get_customer_id_sequence') !== false) {
        $result = $post->get_customer_id_sequence();
        echo json_encode($result);
        exit();
    }
    
    if (strpos($request, 'get_all_employees') !== false) {
        $result = $get->get_all_employees();
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'get_bookings_by_customer') !== false) {
        if (isset($_GET['customer_id'])) {
            $customerId = $_GET['customer_id'];
            $result = $get->get_bookings_by_customer($customerId);
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Customer ID is required.']);
        }
        exit();
    }
    
    if (strpos($request, 'services') !== false) {
        $result = $get->get_all_services();
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'get_all_bookings') !== false) {
        $result = $get->get_all_bookings();
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'get_booking_count') !== false) {
        $result = $get->get_booking_count();
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'get_completed_booking_count') !== false) {
        $result = $get->get_completed_booking_count();
        echo json_encode($result);
        exit();
    }
    if (strpos($request, 'get_pending_booking_count') !== false) {
        $result = $get->get_pending_booking_count();
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'get_revenue_analytics') !== false) {
        $result = $get->get_revenue_analytics();
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'get_service_distribution') !== false) {
        $result = $get->get_service_distribution();
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'get_dashboard_summary') !== false) {
        $result = $get->get_dashboard_summary();
        echo json_encode($result);
        exit();
    }

    // New GET routes for updated database schema
    if (strpos($request, 'get_vehicle_types') !== false) {
        $result = $get->get_vehicle_types();
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'get_payment_methods') !== false) {
        $result = $get->get_payment_methods();
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'get_time_slots') !== false) {
        $result = $get->get_time_slots();
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'get_available_time_slots') !== false) {
        if (isset($_GET['date'])) {
            $date = $_GET['date'];
            $result = $get->get_available_time_slots($date);
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Date parameter is required.']);
        }
        exit();
    }

    if (strpos($request, 'get_promotions') !== false) {
        $result = $get->get_promotions();
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'get_service_categories') !== false) {
        $result = $get->get_service_categories();
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'get_customer_feedback') !== false) {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $result = $get->get_customer_feedback($limit);
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'get_employee_schedules') !== false) {
        $employeeId = isset($_GET['employee_id']) ? $_GET['employee_id'] : null;
        $date = isset($_GET['date']) ? $_GET['date'] : null;
        $result = $get->get_employee_schedules($employeeId, $date);
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'get_system_settings') !== false) {
        $result = $get->get_system_settings();
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'get_notifications') !== false) {
        if (isset($_GET['user_id']) && isset($_GET['user_type'])) {
            $userId = $_GET['user_id'];
            $userType = $_GET['user_type'];
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $result = $get->get_notifications($userId, $userType, $limit);
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'User ID and user type are required.']);
        }
        exit();
    }

    if (strpos($request, 'get_booking_details') !== false) {
        if (isset($_GET['booking_id'])) {
            $bookingId = $_GET['booking_id'];
            $result = $get->get_booking_details($bookingId);
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Booking ID is required.']);
        }
        exit();
    }

    if (strpos($request, 'get_booking_history') !== false) {
        if (isset($_GET['booking_id'])) {
            $bookingId = $_GET['booking_id'];
            $result = $get->get_booking_history($bookingId);
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Booking ID is required.']);
        }
        exit();
    }

    if (strpos($request, 'get_bookings_by_employee') !== false) {
        if (isset($_GET['employee_id'])) {
            $employeeId = $_GET['employee_id'];
            $result = $get->get_bookings_by_employee($employeeId);
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Employee ID is required.']);
        }
        exit();
    }
}

// Handle the request
if ($method === 'POST') {
    // Get POST data
    $data = json_decode(file_get_contents("php://input"));
    
    if (strpos($request, 'register_customer') !== false) {
        $result = $post->register_customer($data);
        echo json_encode($result);
        exit();
    }
    
    if (strpos($request, 'login_customer') !== false) {
        $result = $post->login_customer($data);
        echo json_encode($result);
        exit();
    }
    
    if (strpos($request, 'register_admin') !== false) {
        $result = $post->register_admin($data);
        echo json_encode($result);
        exit();
    }
    
    if (strpos($request, 'login_admin') !== false) {
        $result = $post->login_admin($data);
        echo json_encode($result);
        exit();
    }
    
    if (strpos($request, 'register_employee') !== false) {
        $result = $post->register_employee($data);
        echo json_encode($result);
        exit();
    }
    
    if (strpos($request, 'login_employee') !== false) {
        $result = $post->login_employee($data);
        echo json_encode($result);
        exit();
    }
    
    if (strpos($request, 'services') !== false) {
        $result = $post->add_service($data);
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'create_booking') !== false) {
        $result = $post->create_booking($data);
        echo json_encode($result);
        exit();
    }

    // New POST routes for updated database schema
    if (strpos($request, 'add_vehicle_type') !== false) {
        $result = $post->add_vehicle_type($data);
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'add_payment_method') !== false) {
        $result = $post->add_payment_method($data);
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'add_time_slot') !== false) {
        $result = $post->add_time_slot($data);
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'add_promotion') !== false) {
        $result = $post->add_promotion($data);
        exit();
    }

    if (strpos($request, 'add_service_category') !== false) {
        $result = $post->add_service_category($data);
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'add_customer_feedback') !== false) {
        $result = $post->add_customer_feedback($data);
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'add_employee_schedule') !== false) {
        $result = $post->add_employee_schedule($data);
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'add_notification') !== false) {
        $result = $post->add_notification($data);
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'add_booking_promotion') !== false) {
        $result = $post->add_booking_promotion($data);
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'add_booking_history') !== false) {
        $result = $post->add_booking_history($data);
        echo json_encode($result);
        exit();
    }
}

// Handle PUT requests
if ($method === 'PUT') {
    // Debug logging
    error_log("PUT request received: " . $request);
    error_log("Request method: " . $method);
    
    // Get PUT data
    $data = json_decode(file_get_contents("php://input"));
    error_log("PUT data received: " . json_encode($data));
    
    // Check for JWT token for protected routes
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (strpos($request, 'update_customer_profile') !== false) {
        // Process the update
        $result = $put->update_customer_profile($data);
        echo json_encode($result);
        exit();
    }
    
    if (strpos($request, 'services') !== false) {
        // Process the service update
        $result = $put->update_service($data);
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'update_booking_status') !== false) {
        error_log("Processing update_booking_status request");
        $result = $put->update_booking_status($data);
        error_log("update_booking_status result: " . json_encode($result));
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'assign_employee_to_booking') !== false) {
        error_log("Processing assign_employee_to_booking request");
        $result = $put->assign_employee_to_booking($data);
        error_log("assign_employee_to_booking result: " . json_encode($result));
        echo json_encode($result);
        exit();
    }
    
    // Test endpoint to verify routing
    if (strpos($request, 'test_put') !== false) {
        error_log("Test PUT endpoint reached");
        echo json_encode(['status' => 'success', 'message' => 'PUT routing is working', 'request' => $request]);
        exit();
    }

    // New PUT routes for updated database schema
    if (strpos($request, 'update_vehicle_type') !== false) {
        $result = $put->update_vehicle_type($data);
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'update_payment_method') !== false) {
        $result = $put->update_payment_method($data);
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'update_time_slot') !== false) {
        $result = $put->update_time_slot($data);
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'update_promotion') !== false) {
        $result = $put->update_promotion($data);
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'update_service_category') !== false) {
        $result = $put->update_service_category($data);
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'update_employee_schedule') !== false) {
        $result = $put->update_employee_schedule($data);
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'update_notification_status') !== false) {
        $result = $put->update_notification_status($data);
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'update_system_setting') !== false) {
        $result = $put->update_system_setting($data);
        echo json_encode($result);
        exit();
    }
}

// Handle DELETE requests
if ($method === 'DELETE') {
    // Extract ID from URL for delete operations
    $parts = explode('/', $request);
    $id = end($parts);
    
    if (strpos($request, 'services') !== false && is_numeric($id)) {
        $result = $post->delete_service($id);
        echo json_encode($result);
        exit();
    }
    
    if (strpos($request, 'customers') !== false && is_numeric($id)) {
        $result = $post->delete_customer($id);
        echo json_encode($result);
        exit();
    }
    
    if (strpos($request, 'bookings') !== false && is_numeric($id)) {
        $result = $post->delete_booking($id);
        echo json_encode($result);
        exit();
    }
}
