<?php
// Production-safe: disable verbose error output on public hosting
// (Some free hosts flag ini_set/error_reporting as dangerous)

require_once "./config/env.php";
loadEnv(__DIR__ . '/.env');

// Include required modules
require_once "./autoload.php";
require_once "./modules/get.php";
require_once "./modules/post.php";
require_once "./modules/put.php";
require_once "./config/database.php";

// Force CORS headers to be set for ALL requests
// This is critical for InfinityFree hosting
if (!headers_sent()) {
    // Always set CORS headers regardless of origin
    header('Access-Control-Allow-Origin: https://autowash-hub.vercel.app');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
    
    // Debug logging
    error_log("CORS Headers Set - Origin: " . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'none'));
}

// Handle OPTIONS preflight request FIRST - before any other processing
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    error_log("Handling OPTIONS preflight request");
    
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

// Debug logging for troubleshooting (remove in production)
error_log("API Request - Method: " . $_SERVER['REQUEST_METHOD'] . ", URI: " . $_SERVER['REQUEST_URI'] . ", Origin: " . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'none'));

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

// CORS preflight already handled at the top of the file

// Handle GET requests
if ($method === 'GET') {
    // Test endpoint for CORS debugging
    if (strpos($request, 'test_cors') !== false) {
        error_log("Test CORS endpoint called");
        echo json_encode([
            'status' => 'success',
            'message' => 'CORS test successful',
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $method,
            'request' => $request,
            'origin' => isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'none'
        ]);
        exit();
    }
    
    // Test POST CORS endpoint
    if (strpos($request, 'test_post_cors') !== false) {
        error_log("Test POST CORS endpoint called");
        echo json_encode([
            'status' => 'success',
            'message' => 'POST CORS test successful',
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $method,
            'request' => $request,
            'origin' => isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'none'
        ]);
        exit();
    }
    
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

    // Inventory routes
    if (strpos($request, 'get_inventory_requests') !== false) {
        $result = $get->get_inventory_requests();
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'get_inventory') !== false) {
        $result = $get->get_inventory();
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

    if (strpos($request, 'get_contact_enquiries') !== false) {
        $result = $get->get_contact_enquiries();
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
    
    // Debug logging for POST requests
    error_log("POST Request - URI: " . $request . ", Data: " . json_encode($data));
    
    // Test POST CORS endpoint
    if (strpos(strtolower($request), 'test_post_cors') !== false) {
        error_log("Test POST CORS endpoint called via POST");
        echo json_encode([
            'status' => 'success',
            'message' => 'POST CORS test successful',
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $method,
            'request' => $request,
            'origin' => isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'none',
            'data_received' => $data
        ]);
        exit();
    }
    
    if (strpos(strtolower($request), 'register_customer') !== false) {
        error_log("Handling register_customer request");
        $result = $post->register_customer($data);
        echo json_encode($result);
        exit();
    }
    
    if (strpos(strtolower($request), 'login_customer') !== false) {
        error_log("Handling login_customer request");
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

    if (strpos($request, 'submit_contact') !== false) {
        $result = $post->submit_contact($data);
        echo json_encode($result);
        exit();
    }

    if (strpos($request, 'update_contact_status') !== false) {
        $result = $post->update_contact_status($data);
        echo json_encode($result);
        exit();
    }

    // Inventory create
    if (strpos($request, 'add_inventory_item') !== false) {
        $result = $post->add_inventory_item($data);
        echo json_encode($result);
        exit();
    }

    // Inventory request
    if (strpos($request, 'add_inventory_request') !== false) {
        $result = $post->add_inventory_request($data);
        echo json_encode($result);
        exit();
    }

    // Take inventory item for employee
    if (strpos($request, 'take_inventory_item') !== false) {
        $result = $post->take_inventory_item($data);
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

    if (strpos($request, 'update_employee') !== false) {
        $result = $put->update_employee($data);
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

    // Inventory update
    if (strpos($request, 'update_inventory_item') !== false) {
        $result = $put->update_inventory_item($data);
        echo json_encode($result);
        exit();
    }

    // Inventory request update
    if (strpos($request, 'update_inventory_request') !== false) {
        $result = $put->update_inventory_request($data);
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
    
    if (strpos($request, 'employees') !== false && is_numeric($id)) {
        $result = $post->delete_employee($id);
        echo json_encode($result);
        exit();
    }
    
    if (strpos($request, 'bookings') !== false && is_numeric($id)) {
        $result = $post->delete_booking($id);
        echo json_encode($result);
        exit();
    }

    // Inventory delete
    if (strpos($request, 'inventory') !== false && is_numeric($id)) {
        $result = $post->delete_inventory_item($id);
        echo json_encode($result);
        exit();
    }

    // Contact enquiry delete
    if (strpos($request, 'delete_contact_enquiry') !== false && is_numeric($id)) {
        $result = $post->delete_contact_enquiry($id);
        echo json_encode($result);
        exit();
    }
}
