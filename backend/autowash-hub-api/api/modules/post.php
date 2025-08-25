<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "global.php";

use Firebase\JWT\JWT;

class Post extends GlobalMethods
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function executeQuery($sql)
    {
        $data = array();
        $errmsg = "";
        $code = 0;

        try {
            if ($result = $this->pdo->query($sql)->fetchAll()) {
                foreach ($result as $record) {
                    array_push($data, $record);
                }
                $code = 200;
                $result = null;
                return array("code" => $code, "data" => $data);
            } else {
                // if no record found, assign corresponding values to error messages/status
                $errmsg = "No records found";
                $code = 404;
            }
        } catch (\PDOException $e) {
            // PDO errors, mysql errors
            $errmsg = $e->getMessage();
            $code = 403;
        }
        return array("code" => $code, "errmsg" => $errmsg);
    }

    private function findNextAvailableCustomerId() {
        try {
            // First, try to find gaps in the ID sequence starting from 1
            $sql = "SELECT MIN(t1.id + 1) as next_id 
                    FROM customers t1 
                    LEFT JOIN customers t2 ON t1.id + 1 = t2.id 
                    WHERE t2.id IS NULL";
            $statement = $this->pdo->prepare($sql);
            $statement->execute();
            $result = $statement->fetch(PDO::FETCH_ASSOC);
            
            // If gaps found, use the first available ID
            if ($result['next_id'] !== null) {
                return (int)$result['next_id'];
            }
            
            // If no gaps found, use the next sequential ID
            $sql = "SELECT COALESCE(MAX(id) + 1, 1) as next_id FROM customers";
            $statement = $this->pdo->prepare($sql);
            $statement->execute();
            $result = $statement->fetch(PDO::FETCH_ASSOC);
            
            return (int)$result['next_id'];
            
        } catch (\PDOException $e) {
            error_log("Error finding next available customer ID: " . $e->getMessage());
            // Fallback: return 1 if there's an error
            return 1;
        }
    }

    private function resetCustomerAutoIncrement() {
        try {
            // Reset the auto-increment counter to the next available ID
            $sql = "SELECT COALESCE(MAX(id) + 1, 1) as next_id FROM customers";
            $statement = $this->pdo->prepare($sql);
            $statement->execute();
            $result = $statement->fetch(PDO::FETCH_ASSOC);
            
            $nextId = (int)$result['next_id'];
            
            // Reset auto-increment to the next available ID
            $sql = "ALTER TABLE customers AUTO_INCREMENT = ?";
            $statement = $this->pdo->prepare($sql);
            $statement->execute([$nextId]);
            
        } catch (\PDOException $e) {
            error_log("Error resetting customer auto-increment: " . $e->getMessage());
            // Continue execution even if reset fails
        }
    }

    public function register_customer($data)
    {
        // Validate required fields
        if (empty($data->first_name) || empty($data->email) || empty($data->password)) {
            return $this->sendPayload(null, "failed", "Missing required fields", 400);
        }

        // Validate email format
        if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
            return $this->sendPayload(null, "failed", "Invalid email format", 400);
        }

        try {
            // Check if email already exists
            $sql = "SELECT COUNT(*) FROM customers WHERE email = ?";
            $statement = $this->pdo->prepare($sql);
            $statement->execute([$data->email]);
            $count = $statement->fetchColumn();

            if ($count > 0) {
                return $this->sendPayload(null, "failed", "Email already registered", 400);
            }
        
            // Find the next available ID starting from 1
            $nextId = $this->findNextAvailableCustomerId();
            
            // Proceed with registration using custom ID
            $sql = "INSERT INTO customers (id, first_name, last_name, email, phone, password) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $statement = $this->pdo->prepare($sql);
            $hashedPassword = password_hash($data->password, PASSWORD_BCRYPT);

            $statement->execute([
                $nextId,
                $data->first_name,
                $data->last_name ?? '',
                $data->email,
                $data->phone ?? '',
                $hashedPassword
            ]);

            if ($statement->rowCount() > 0) {
                return $this->sendPayload(null, "success", "Successfully registered", 200);
            } else {
                return $this->sendPayload(null, "failed", "Registration failed", 400);
            }

        } catch (\PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            return $this->sendPayload(
                null, 
                "failed", 
                "Database error occurred. Please try again.", 
                500
            );
        }
    }
    
    public function login_customer($data) {
        // Validate required fields
        if (empty($data->email) || empty($data->password)) {
            return $this->sendPayload(null, "failed", "Email and password are required", 400);
        }

        try {
            // Get customer by email
            $sql = "SELECT * FROM customers WHERE email = ?";
            $statement = $this->pdo->prepare($sql);
            $statement->execute([$data->email]);
            $customer = $statement->fetch(PDO::FETCH_ASSOC);

            // Check if customer exists and verify password
            if ($customer && password_verify($data->password, $customer['password'])) {
                // Generate JWT token
                $key = getenv('JWT_SECRET') ?: 'default_secret_key';
                $payload = [
                    'iss' => 'autowash_hub',
                    'aud' => 'customer',
                    'iat' => time(),
                    'exp' => time() + (60 * 60 * 24), // 24 hours
                    'data' => [
                        'id' => $customer['id'],
                        'email' => $customer['email'],
                        'first_name' => $customer['first_name'],
                        'last_name' => $customer['last_name']
                    ]
                ];
                
                $jwt = JWT::encode($payload, $key, 'HS256');
                
                // Remove password from customer data
                unset($customer['password']);
                
                return $this->sendPayload(
                    [
                        'token' => $jwt,
                        'customer' => $customer
                    ],
                    "success",
                    "Login successful",
                    200
                );
            } else {
                return $this->sendPayload(null, "failed", "Invalid email or password", 401);
            }
        } catch (\PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return $this->sendPayload(
                null,
                "failed",
                "Database error occurred. Please try again.",
                500
            );
        }
    }
    
    public function register_admin($data) {
        // Validate required fields
        if (empty($data->first_name) || empty($data->email) || empty($data->password) || empty($data->admin_id)) {
            return $this->sendPayload(null, "failed", "Missing required fields", 400);
        }

        // Validate email format
        if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
            return $this->sendPayload(null, "failed", "Invalid email format", 400);
        }
        
        // Admin key check temporarily removed

        try {
            // Check if email already exists
            $sql = "SELECT COUNT(*) FROM admins WHERE email = ?";
            $statement = $this->pdo->prepare($sql);
            $statement->execute([$data->email]);
            $count = $statement->fetchColumn();

            if ($count > 0) {
                return $this->sendPayload(null, "failed", "Email already registered", 400);
            }
            
            // Check if admin_id already exists
            $sql = "SELECT COUNT(*) FROM admins WHERE admin_id = ?";
            $statement = $this->pdo->prepare($sql);
            $statement->execute([$data->admin_id]);
            $count = $statement->fetchColumn();

            if ($count > 0) {
                return $this->sendPayload(null, "failed", "Admin ID already exists", 400);
            }
        
            // Proceed with registration
            $sql = "INSERT INTO admins (admin_id, first_name, last_name, email, phone, password) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $statement = $this->pdo->prepare($sql);
            $hashedPassword = password_hash($data->password, PASSWORD_BCRYPT);

            $statement->execute([
                $data->admin_id,
                $data->first_name,
                $data->last_name ?? '',
                $data->email,
                $data->phone ?? '',
                $hashedPassword
            ]);

            if ($statement->rowCount() > 0) {
                return $this->sendPayload(null, "success", "Admin successfully registered", 200);
            } else {
                return $this->sendPayload(null, "failed", "Registration failed", 400);
            }

        } catch (\PDOException $e) {
            error_log("Admin registration error: " . $e->getMessage());
            return $this->sendPayload(
                null, 
                "failed", 
                "Database error occurred. Please try again.", 
                500
            );
        }
    }
    
    public function login_admin($data) {
        // Validate required fields
        if (empty($data->email) || empty($data->password)) {
            return $this->sendPayload(null, "failed", "Email and password are required", 400);
        }

        try {
            // Get admin by email
            $sql = "SELECT * FROM admins WHERE email = ?";
            $statement = $this->pdo->prepare($sql);
            $statement->execute([$data->email]);
            $admin = $statement->fetch(PDO::FETCH_ASSOC);

            // Check if admin exists and verify password
            if ($admin && password_verify($data->password, $admin['password'])) {
                // Generate JWT token
                $key = getenv('JWT_SECRET') ?: 'default_secret_key';
                $payload = [
                    'iss' => 'autowash_hub',
                    'aud' => 'admin',
                    'iat' => time(),
                    'exp' => time() + (60 * 60 * 24), // 24 hours
                    'data' => [
                        'id' => $admin['id'],
                        'email' => $admin['email'],
                        'first_name' => $admin['first_name'],
                        'last_name' => $admin['last_name']
                    ]
                ];
                
                $jwt = JWT::encode($payload, $key, 'HS256');
                
                // Remove password from admin data
                unset($admin['password']);
                
                return $this->sendPayload(
                    [
                        'token' => $jwt,
                        'admin' => $admin
                    ],
                    "success",
                    "Login successful",
                    200
                );
            } else {
                return $this->sendPayload(null, "failed", "Invalid email or password", 401);
            }
        } catch (\PDOException $e) {
            error_log("Admin login error: " . $e->getMessage());
            return $this->sendPayload(
                null,
                "failed",
                "Database error occurred. Please try again.",
                500
            );
        }
    }

    public function register_employee($data) {
        // Validate required fields
        if (empty($data->first_name) || empty($data->email) || empty($data->password) || empty($data->employee_id) || empty($data->position)) {
            return $this->sendPayload(null, "failed", "Missing required fields", 400);
        }

        // Validate email format
        if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
            return $this->sendPayload(null, "failed", "Invalid email format", 400);
        }

        try {
            // Check if email already exists
            $sql = "SELECT COUNT(*) FROM employees WHERE email = ?";
            $statement = $this->pdo->prepare($sql);
            $statement->execute([$data->email]);
            $count = $statement->fetchColumn();

            if ($count > 0) {
                return $this->sendPayload(null, "failed", "Email already registered", 400);
            }
            
            // Check if employee_id already exists
            $sql = "SELECT COUNT(*) FROM employees WHERE employee_id = ?";
            $statement = $this->pdo->prepare($sql);
            $statement->execute([$data->employee_id]);
            $count = $statement->fetchColumn();

            if ($count > 0) {
                return $this->sendPayload(null, "failed", "Employee ID already exists", 400);
            }
        
            // Proceed with registration
            $sql = "INSERT INTO employees (employee_id, first_name, last_name, email, phone, password, position) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $statement = $this->pdo->prepare($sql);
            $hashedPassword = password_hash($data->password, PASSWORD_BCRYPT);

            $statement->execute([
                $data->employee_id,
                $data->first_name,
                $data->last_name ?? '',
                $data->email,
                $data->phone ?? '',
                $hashedPassword,
                $data->position
            ]);

            if ($statement->rowCount() > 0) {
                return $this->sendPayload(null, "success", "Employee successfully registered", 200);
            } else {
                return $this->sendPayload(null, "failed", "Registration failed", 400);
            }

        } catch (\PDOException $e) {
            error_log("Employee registration error: " . $e->getMessage());
            return $this->sendPayload(
                null, 
                "failed", 
                "Database error occurred. Please try again.", 
                500
            );
        }
    }

    public function login_employee($data) {
        // Validate required fields
        if (empty($data->email) || empty($data->password)) {
            return $this->sendPayload(null, "failed", "Email and password are required", 400);
        }

        try {
            // Get employee by email
            $sql = "SELECT * FROM employees WHERE email = ?";
            $statement = $this->pdo->prepare($sql);
            $statement->execute([$data->email]);
            $employee = $statement->fetch(PDO::FETCH_ASSOC);

            // Check if employee exists and verify password
            if ($employee && password_verify($data->password, $employee['password'])) {
                // Generate JWT token
                $key = getenv('JWT_SECRET') ?: 'default_secret_key';
                $payload = [
                    'iss' => 'autowash_hub',
                    'aud' => 'employee',
                    'iat' => time(),
                    'exp' => time() + (60 * 60 * 8), // 8 hours
                    'data' => [
                        'id' => $employee['id'],
                        'employee_id' => $employee['employee_id'],
                        'email' => $employee['email'],
                        'first_name' => $employee['first_name'],
                        'last_name' => $employee['last_name'],
                        'position' => $employee['position']
                    ]
                ];
                
                $jwt = JWT::encode($payload, $key, 'HS256');
                
                // Remove password from employee data
                unset($employee['password']);
                
                return $this->sendPayload(
                    [
                        'token' => $jwt,
                        'employee' => $employee
                    ],
                    "success",
                    "Login successful",
                    200
                );
            } else {
                return $this->sendPayload(null, "failed", "Invalid email or password", 401);
            }
        } catch (\PDOException $e) {
            error_log("Employee login error: " . $e->getMessage());
            return $this->sendPayload(
                null,
                "failed",
                "Database error occurred. Please try again.",
                500
            );
        }
    }

    public function create_booking($data) {
        // Validate required fields
        if (
            empty($data->customer_id) || 
            empty($data->service_id) || 
            empty($data->vehicle_type) || 
            empty($data->nickname) || 
            empty($data->phone) || 
            empty($data->wash_date) || 
            empty($data->wash_time) || 
            empty($data->payment_type) ||
            !isset($data->price)
        ) {
            return $this->sendPayload(null, "failed", "Missing required booking fields", 400);
        }

        // Validate online payment option if Online Payment is selected
        if ($data->payment_type === 'Online Payment' && empty($data->online_payment_option)) {
            return $this->sendPayload(null, "failed", "Online payment method is required when selecting Online Payment", 400);
        }

        try {
            // Find the next available booking ID starting from 1
            $nextId = $this->get_next_booking_id();
            
            // Prepare payment type string
            $paymentTypeString = $data->payment_type;
            if ($data->payment_type === 'Online Payment' && !empty($data->online_payment_option)) {
                $paymentTypeString = $data->payment_type . ' - ' . $data->online_payment_option;
            }
            
            $sql = "INSERT INTO bookings (id, customer_id, service_id, vehicle_type, nickname, phone, wash_date, wash_time, payment_type, price, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $statement = $this->pdo->prepare($sql);

            $statement->execute([
                $nextId,
                $data->customer_id,
                $data->service_id,
                $data->vehicle_type,
                $data->nickname,
                $data->phone,
                $data->wash_date,
                $data->wash_time,
                $paymentTypeString,
                $data->price,
                $data->notes ?? null
            ]);

            if ($statement->rowCount() > 0) {
                return $this->sendPayload(["booking_id" => $nextId], "success", "Booking created successfully", 201);
            } else {
                return $this->sendPayload(null, "failed", "Failed to create booking", 400);
            }

        } catch (\PDOException $e) {
            error_log("Booking creation error: " . $e->getMessage());
            return $this->sendPayload(
                null, 
                "failed", 
                "A database error occurred.", 
                500
            );
        }
    }

    /**
     * Get the next available booking ID starting from 1
     * This ensures IDs always start from 1 and fill gaps when bookings are deleted
     */
    private function get_next_booking_id() {
        try {
            // Get all existing booking IDs, sorted
            $sql = "SELECT id FROM bookings ORDER BY id ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $existingIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // If no bookings exist, start with ID 1
            if (empty($existingIds)) {
                return 1;
            }
            
            // Find the first gap in the sequence, starting from 1
            $expectedId = 1;
            foreach ($existingIds as $existingId) {
                if ($existingId != $expectedId) {
                    // Found a gap, use this ID
                    return $expectedId;
                }
                $expectedId++;
            }
            
            // No gaps found, use the next ID after the highest existing ID
            return $expectedId;
            
        } catch (\PDOException $e) {
            error_log("Error getting next booking ID: " . $e->getMessage());
            // Fallback to auto-increment behavior
            return null;
        }
    }

    /**
     * Delete a booking by ID
     */
    public function delete_booking($id) {
        // Validate booking ID
        if (!is_numeric($id) || $id <= 0) {
            return $this->sendPayload(null, "failed", "Invalid booking ID", 400);
        }

        try {
            // Check if the booking exists
            $sql = "SELECT COUNT(*) FROM bookings WHERE id = ?";
            $statement = $this->pdo->prepare($sql);
            $statement->execute([$id]);
            $count = $statement->fetchColumn();

            if ($count == 0) {
                return $this->sendPayload(null, "failed", "Booking not found", 404);
            }
            
            // Delete the booking
            $sql = "DELETE FROM bookings WHERE id = ?";
            $statement = $this->pdo->prepare($sql);
            $statement->execute([$id]);

            if ($statement->rowCount() > 0) {
                // Reset auto-increment if this was the last booking
                $this->reset_booking_auto_increment();
                
                return $this->sendPayload(null, "success", "Booking deleted successfully", 200);
            } else {
                return $this->sendPayload(null, "failed", "Failed to delete booking", 400);
            }

        } catch (\PDOException $e) {
            error_log("Booking deletion error: " . $e->getMessage());
            return $this->sendPayload(
                null, 
                "failed", 
                "Database error occurred: " . $e->getMessage(), 
                500
            );
        }
    }

    /**
     * Reset the auto-increment counter for bookings table
     * This ensures the next auto-generated ID starts from 1 when all bookings are deleted
     */
    private function reset_booking_auto_increment() {
        try {
            // Check if there are any remaining bookings
            $sql = "SELECT COUNT(*) FROM bookings";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            // If no bookings remain, reset the auto-increment to 1
            if ($count == 0) {
                $sql = "ALTER TABLE bookings AUTO_INCREMENT = 1";
                $this->pdo->exec($sql);
            }
        } catch (\PDOException $e) {
            error_log("Error resetting booking auto-increment: " . $e->getMessage());
        }
    }

    public function add_service($data) {
        // Validate required fields
        if (empty($data->name) || empty($data->price) || empty($data->duration_minutes) || empty($data->category)) {
            return $this->sendPayload(null, "failed", "Missing required fields", 400);
        }

        try {
            // Prepare the SQL query to insert a new service
            $sql = "INSERT INTO services (name, description, price, duration_minutes, category, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $statement = $this->pdo->prepare($sql);
            
            // Set is_active to 1 if true, 0 if false
            $isActive = isset($data->is_active) ? ($data->is_active ? 1 : 0) : 1;

            $statement->execute([
                $data->name,
                $data->description ?? '',
                $data->price,
                $data->duration_minutes,
                $data->category,
                $isActive
            ]);

            if ($statement->rowCount() > 0) {
                // Get the newly created service ID
                $serviceId = $this->pdo->lastInsertId();
                
                // Fetch the created service
                $sql = "SELECT id, name, description, price, duration_minutes, category, is_active, created_at, updated_at 
                       FROM services WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$serviceId]);
                $service = $stmt->fetch(PDO::FETCH_ASSOC);
                
                return $this->sendPayload(
                    ['service' => $service], 
                    "success", 
                    "Service added successfully", 
                    200
                );
            } else {
                return $this->sendPayload(null, "failed", "Failed to add service", 400);
            }

        } catch (\PDOException $e) {
            error_log("Service creation error: " . $e->getMessage());
            return $this->sendPayload(
                null, 
                "failed", 
                "A database error occurred.", 
                500
            );
        }
    }

    // New methods for the updated database schema
    public function add_vehicle_type($data) {
        // Validate required fields
        if (empty($data->name) || !isset($data->base_price_multiplier)) {
            return $this->sendPayload(null, "failed", "Missing required fields", 400);
        }

        try {
            $sql = "INSERT INTO vehicle_types (name, description, base_price_multiplier, is_active) 
                    VALUES (?, ?, ?, ?)";
            
            $statement = $this->pdo->prepare($sql);
            $isActive = isset($data->is_active) ? ($data->is_active ? 1 : 0) : 1;

            $statement->execute([
                $data->name,
                $data->description ?? '',
                $data->base_price_multiplier,
                $isActive
            ]);

            if ($statement->rowCount() > 0) {
                $vehicleTypeId = $this->pdo->lastInsertId();
                
                // Fetch the created vehicle type
                $sql = "SELECT id, name, description, base_price_multiplier, is_active, created_at 
                       FROM vehicle_types WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$vehicleTypeId]);
                $vehicleType = $stmt->fetch(PDO::FETCH_ASSOC);
                
                return $this->sendPayload(
                    ['vehicle_type' => $vehicleType], 
                    "success", 
                    "Vehicle type added successfully", 
                    200
                );
            } else {
                return $this->sendPayload(null, "failed", "Failed to add vehicle type", 400);
            }

        } catch (\PDOException $e) {
            error_log("Vehicle type creation error: " . $e->getMessage());
            return $this->sendPayload(
                null, 
                "failed", 
                "A database error occurred.", 
                500
            );
        }
    }

    public function add_payment_method($data) {
        // Validate required fields
        if (empty($data->name)) {
            return $this->sendPayload(null, "failed", "Missing required fields", 400);
        }

        try {
            $sql = "INSERT INTO payment_methods (name, description, is_active) 
                    VALUES (?, ?, ?)";
            
            $statement = $this->pdo->prepare($sql);
            $isActive = isset($data->is_active) ? ($data->is_active ? 1 : 0) : 1;

            $statement->execute([
                $data->name,
                $data->description ?? '',
                $isActive
            ]);

            if ($statement->rowCount() > 0) {
                $paymentMethodId = $this->pdo->lastInsertId();
                
                // Fetch the created payment method
                $sql = "SELECT id, name, description, is_active, created_at 
                       FROM payment_methods WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$paymentMethodId]);
                $paymentMethod = $stmt->fetch(PDO::FETCH_ASSOC);
                
                return $this->sendPayload(
                    ['payment_method' => $paymentMethod], 
                    "success", 
                    "Payment method added successfully", 
                    200
                );
            } else {
                return $this->sendPayload(null, "failed", "Failed to add payment method", 400);
            }

        } catch (\PDOException $e) {
            error_log("Payment method creation error: " . $e->getMessage());
            return $this->sendPayload(
                null, 
                "failed", 
                "A database error occurred.", 
                500
            );
        }
    }

    public function add_time_slot($data) {
        // Validate required fields
        if (empty($data->start_time) || empty($data->end_time) || !isset($data->max_bookings)) {
            return $this->sendPayload(null, "failed", "Missing required fields", 400);
        }

        try {
            $sql = "INSERT INTO time_slots (start_time, end_time, max_bookings, is_active) 
                    VALUES (?, ?, ?, ?)";
            
            $statement = $this->pdo->prepare($sql);
            $isActive = isset($data->is_active) ? ($data->is_active ? 1 : 0) : 1;

            $statement->execute([
                $data->start_time,
                $data->end_time,
                $data->max_bookings,
                $isActive
            ]);

            if ($statement->rowCount() > 0) {
                $timeSlotId = $this->pdo->lastInsertId();
                
                // Fetch the created time slot
                $sql = "SELECT id, start_time, end_time, max_bookings, is_active, created_at 
                       FROM time_slots WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$timeSlotId]);
                $timeSlot = $stmt->fetch(PDO::FETCH_ASSOC);
                
                return $this->sendPayload(
                    ['time_slot' => $timeSlot], 
                    "success", 
                    "Time slot added successfully", 
                    200
                );
            } else {
                return $this->sendPayload(null, "failed", "Failed to add time slot", 400);
            }

        } catch (\PDOException $e) {
            error_log("Time slot creation error: " . $e->getMessage());
            return $this->sendPayload(
                null, 
                "failed", 
                "A database error occurred.", 
                500
            );
        }
    }

    public function add_promotion($data) {
        // Validate required fields
        if (empty($data->name) || !isset($data->discount_percentage) || empty($data->start_date) || empty($data->end_date)) {
            return $this->sendPayload(null, "failed", "Missing required fields", 400);
        }

        try {
            $sql = "INSERT INTO promotions (name, description, discount_percentage, start_date, end_date, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $statement = $this->pdo->prepare($sql);
            $isActive = isset($data->is_active) ? ($data->is_active ? 1 : 0) : 1;

            $statement->execute([
                $data->name,
                $data->description ?? '',
                $data->discount_percentage,
                $data->start_date,
                $data->end_date,
                $isActive
            ]);

            if ($statement->rowCount() > 0) {
                $promotionId = $this->pdo->lastInsertId();
                
                // Fetch the created promotion
                $sql = "SELECT id, name, description, discount_percentage, start_date, end_date, is_active, created_at 
                       FROM promotions WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$promotionId]);
                $promotion = $stmt->fetch(PDO::FETCH_ASSOC);
                
                return $this->sendPayload(
                    ['promotion' => $promotion], 
                    "success", 
                    "Promotion added successfully", 
                    200
                );
            } else {
                return $this->sendPayload(null, "failed", "Failed to add promotion", 400);
            }

        } catch (\PDOException $e) {
            error_log("Promotion creation error: " . $e->getMessage());
            return $this->sendPayload(
                null, 
                "failed", 
                "A database error occurred.", 
                500
            );
        }
    }

    public function add_service_category($data) {
        // Validate required fields
        if (empty($data->name)) {
            return $this->sendPayload(null, "failed", "Missing required fields", 400);
        }

        try {
            $sql = "INSERT INTO service_categories (name, description, is_active) 
                    VALUES (?, ?, ?)";
            
            $statement = $this->pdo->prepare($sql);
            $isActive = isset($data->is_active) ? ($data->is_active ? 1 : 0) : 1;

            $statement->execute([
                $data->name,
                $data->description ?? '',
                $isActive
            ]);

            if ($statement->rowCount() > 0) {
                $categoryId = $this->pdo->lastInsertId();
                
                // Fetch the created category
                $sql = "SELECT id, name, description, is_active, created_at 
                       FROM service_categories WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$categoryId]);
                $category = $stmt->fetch(PDO::FETCH_ASSOC);
                
                return $this->sendPayload(
                    ['service_category' => $category], 
                    "success", 
                    "Service category added successfully", 
                    200
                );
            } else {
                return $this->sendPayload(null, "failed", "Failed to add service category", 400);
            }

        } catch (\PDOException $e) {
            error_log("Service category creation error: " . $e->getMessage());
            return $this->sendPayload(
                null, 
                "failed", 
                "A database error occurred.", 
                500
            );
        }
    }

    public function add_customer_feedback($data) {
        // Validate required fields
        if (empty($data->booking_id) || empty($data->customer_id) || !isset($data->rating)) {
            return $this->sendPayload(null, "failed", "Missing required fields", 400);
        }

        // Validate rating range
        if ($data->rating < 1 || $data->rating > 5) {
            return $this->sendPayload(null, "failed", "Rating must be between 1 and 5", 400);
        }

        try {
            $sql = "INSERT INTO customer_feedback (booking_id, customer_id, rating, comment, is_public) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $statement = $this->pdo->prepare($sql);
            $isPublic = isset($data->is_public) ? ($data->is_public ? 1 : 0) : 1;

            $statement->execute([
                $data->booking_id,
                $data->customer_id,
                $data->rating,
                $data->comment ?? '',
                $isPublic
            ]);

            if ($statement->rowCount() > 0) {
                $feedbackId = $this->pdo->lastInsertId();
                
                // Fetch the created feedback
                $sql = "SELECT id, booking_id, customer_id, rating, comment, is_public, created_at 
                       FROM customer_feedback WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$feedbackId]);
                $feedback = $stmt->fetch(PDO::FETCH_ASSOC);
                
                return $this->sendPayload(
                    ['customer_feedback' => $feedback], 
                    "success", 
                    "Customer feedback added successfully", 
                    200
                );
            } else {
                return $this->sendPayload(null, "failed", "Failed to add customer feedback", 400);
            }

        } catch (\PDOException $e) {
            error_log("Customer feedback creation error: " . $e->getMessage());
            return $this->sendPayload(
                null, 
                "failed", 
                "A database error occurred.", 
                500
            );
        }
    }

    public function add_employee_schedule($data) {
        // Validate required fields
        if (empty($data->employee_id) || empty($data->work_date) || empty($data->start_time) || empty($data->end_time)) {
            return $this->sendPayload(null, "failed", "Missing required fields", 400);
        }

        try {
            $sql = "INSERT INTO employee_schedules (employee_id, work_date, start_time, end_time, is_available) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $statement = $this->pdo->prepare($sql);
            $isAvailable = isset($data->is_available) ? ($data->is_available ? 1 : 0) : 1;

            $statement->execute([
                $data->employee_id,
                $data->work_date,
                $data->start_time,
                $data->end_time,
                $isAvailable
            ]);

            if ($statement->rowCount() > 0) {
                $scheduleId = $this->pdo->lastInsertId();
                
                // Fetch the created schedule
                $sql = "SELECT id, employee_id, work_date, start_time, end_time, is_available, created_at 
                       FROM employee_schedules WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$scheduleId]);
                $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
                
                return $this->sendPayload(
                    ['employee_schedule' => $schedule], 
                    "success", 
                    "Employee schedule added successfully", 
                    200
                );
            } else {
                return $this->sendPayload(null, "failed", "Failed to add employee schedule", 400);
            }

        } catch (\PDOException $e) {
            error_log("Employee schedule creation error: " . $e->getMessage());
            return $this->sendPayload(
                null, 
                "failed", 
                "A database error occurred.", 
                500
            );
        }
    }

    public function add_notification($data) {
        // Validate required fields
        if (empty($data->user_id) || empty($data->user_type) || empty($data->title) || empty($data->message)) {
            return $this->sendPayload(null, "failed", "Missing required fields", 400);
        }

        try {
            $sql = "INSERT INTO notifications (user_id, user_type, title, message, type) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $statement = $this->pdo->prepare($sql);
            $type = $data->type ?? 'info';

            $statement->execute([
                $data->user_id,
                $data->user_type,
                $data->title,
                $data->message,
                $type
            ]);

            if ($statement->rowCount() > 0) {
                $notificationId = $this->pdo->lastInsertId();
                
                // Fetch the created notification
                $sql = "SELECT id, user_id, user_type, title, message, type, is_read, created_at 
                       FROM notifications WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$notificationId]);
                $notification = $stmt->fetch(PDO::FETCH_ASSOC);
                
                return $this->sendPayload(
                    ['notification' => $notification], 
                    "success", 
                    "Notification added successfully", 
                    200
                );
            } else {
                return $this->sendPayload(null, "failed", "Failed to add notification", 400);
            }

        } catch (\PDOException $e) {
            error_log("Notification creation error: " . $e->getMessage());
            return $this->sendPayload(
                null, 
                "failed", 
                "A database error occurred.", 
                500
            );
        }
    }

    public function add_booking_promotion($data) {
        // Validate required fields
        if (empty($data->booking_id) || empty($data->promotion_id) || !isset($data->discount_amount)) {
            return $this->sendPayload(null, "failed", "Missing required fields", 400);
        }

        try {
            $sql = "INSERT INTO booking_promotions (booking_id, promotion_id, discount_amount) 
                    VALUES (?, ?, ?)";
            
            $statement = $this->pdo->prepare($sql);

            $statement->execute([
                $data->booking_id,
                $data->promotion_id,
                $data->discount_amount
            ]);

            if ($statement->rowCount() > 0) {
                $bookingPromotionId = $this->pdo->lastInsertId();
                
                // Fetch the created booking promotion
                $sql = "SELECT id, booking_id, promotion_id, discount_amount, created_at 
                       FROM booking_promotions WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$bookingPromotionId]);
                $bookingPromotion = $stmt->fetch(PDO::FETCH_ASSOC);
                
                return $this->sendPayload(
                    ['booking_promotion' => $bookingPromotion], 
                    "success", 
                    "Booking promotion added successfully", 
                    200
                );
            } else {
                return $this->sendPayload(null, "failed", "Failed to add booking promotion", 400);
            }

        } catch (\PDOException $e) {
            error_log("Booking promotion creation error: " . $e->getMessage());
            return $this->sendPayload(
                null, 
                "failed", 
                "A database error occurred.", 
                500
            );
        }
    }

    public function add_booking_history($data) {
        // Validate required fields
        if (empty($data->booking_id) || empty($data->status_to)) {
            return $this->sendPayload(null, "failed", "Missing required fields", 400);
        }

        try {
            $sql = "INSERT INTO booking_history (booking_id, status_from, status_to, changed_by, notes) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $statement = $this->pdo->prepare($sql);

            $statement->execute([
                $data->booking_id,
                $data->status_from ?? null,
                $data->status_to,
                $data->changed_by ?? null,
                $data->notes ?? null
            ]);

            if ($statement->rowCount() > 0) {
                $historyId = $this->pdo->lastInsertId();
                
                // Fetch the created history record
                $sql = "SELECT id, booking_id, status_from, status_to, changed_by, notes, created_at 
                       FROM booking_history WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$historyId]);
                $history = $stmt->fetch(PDO::FETCH_ASSOC);
                
                return $this->sendPayload(
                    ['booking_history' => $history], 
                    "success", 
                    "Booking history added successfully", 
                    200
                );
            } else {
                return $this->sendPayload(null, "failed", "Failed to add booking history", 400);
            }

        } catch (\PDOException $e) {
            error_log("Booking history creation error: " . $e->getMessage());
            return $this->sendPayload(
                null, 
                "failed", 
                "A database error occurred.", 
                500
            );
        }
    }
    
    public function delete_service($id) {
        // Validate service ID
        if (!is_numeric($id) || $id <= 0) {
            return $this->sendPayload(null, "failed", "Invalid service ID", 400);
        }

        try {
            // Check if the service exists
            $sql = "SELECT COUNT(*) FROM services WHERE id = ?";
            $statement = $this->pdo->prepare($sql);
            $statement->execute([$id]);
            $count = $statement->fetchColumn();

            if ($count == 0) {
                return $this->sendPayload(null, "failed", "Service not found", 404);
            }
            
            // Delete the service
            $sql = "DELETE FROM services WHERE id = ?";
            $statement = $this->pdo->prepare($sql);
            $statement->execute([$id]);

            if ($statement->rowCount() > 0) {
                return $this->sendPayload(null, "success", "Service deleted successfully", 200);
            } else {
                return $this->sendPayload(null, "failed", "Failed to delete service", 400);
            }

        } catch (\PDOException $e) {
            error_log("Service deletion error: " . $e->getMessage());
            return $this->sendPayload(
                null, 
                "failed", 
                "Database error occurred: " . $e->getMessage(), 
                500
            );
        }
	}

    public function delete_customer($id) {
        // Validate customer ID
        if (!is_numeric($id) || $id <= 0) {
            return $this->sendPayload(null, "failed", "Invalid customer ID", 400);
        }

        try {
            // Check if the customer exists
            $sql = "SELECT COUNT(*) FROM customers WHERE id = ?";
            $statement = $this->pdo->prepare($sql);
            $statement->execute([$id]);
            $count = $statement->fetchColumn();

            if ($count == 0) {
                return $this->sendPayload(null, "failed", "Customer not found", 404);
            }
            
            // Check if customer has any related records (bookings, feedback, etc.)
            // This prevents deletion if customer has associated data
            $sql = "SELECT 
                        (SELECT COUNT(*) FROM bookings WHERE customer_id = ?) as booking_count,
                        (SELECT COUNT(*) FROM customer_feedback WHERE customer_id = ?) as feedback_count";
            $statement = $this->pdo->prepare($sql);
            $statement->execute([$id, $id]);
            $relatedData = $statement->fetch(PDO::FETCH_ASSOC);
            
            if ($relatedData['booking_count'] > 0 || $relatedData['feedback_count'] > 0) {
                return $this->sendPayload(
                    null, 
                    "failed", 
                    "Cannot delete customer with existing bookings or feedback. Please archive instead.", 
                    400
                );
            }
            
            // Delete the customer
            $sql = "DELETE FROM customers WHERE id = ?";
            $statement = $this->pdo->prepare($sql);
            $statement->execute([$id]);

            if ($statement->rowCount() > 0) {
                // Reset auto-increment counter to maintain proper ID sequence
                $this->resetCustomerAutoIncrement();
                
                return $this->sendPayload(null, "success", "Customer deleted successfully", 200);
            } else {
                return $this->sendPayload(null, "failed", "Failed to delete customer", 400);
            }

        } catch (\PDOException $e) {
            error_log("Customer deletion error: " . $e->getMessage());
            return $this->sendPayload(
                null, 
                "failed", 
                "Database error occurred: " . $e->getMessage(), 
                500
            );
        }
    }

    public function get_customer_id_sequence() {
        try {
            $sql = "SELECT id FROM customers ORDER BY id ASC";
            $statement = $this->pdo->prepare($sql);
            $statement->execute();
            $ids = $statement->fetchAll(PDO::FETCH_COLUMN);
            
            return $this->sendPayload(
                ['customer_ids' => $ids],
                "success",
                "Customer ID sequence retrieved successfully",
                200
            );
        } catch (\PDOException $e) {
            error_log("Error getting customer ID sequence: " . $e->getMessage());
            return $this->sendPayload(
                null,
                "failed",
                "Failed to retrieve customer ID sequence: " . $e->getMessage(),
                500
            );
        }
    }
}
