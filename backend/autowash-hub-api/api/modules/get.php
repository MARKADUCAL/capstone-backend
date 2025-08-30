<?php


require_once "global.php";

class Get extends GlobalMethods {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function executeQuery($sql) {
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
    
    public function get_customer_count() {
        try {
            $sql = "SELECT COUNT(*) as total_customers FROM customers";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->sendPayload(
                ['total_customers' => $result['total_customers']],
                "success",
                "Customer count retrieved successfully",
                200
            );
        } catch (\PDOException $e) {
            return $this->sendPayload(
                null,
                "failed",
                "Failed to retrieve customer count: " . $e->getMessage(),
                500
            );
        }
    }

    public function get_employee_count() {
        try {
            $sql = "SELECT COUNT(*) as total_employees FROM employees";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->sendPayload(
                ['total_employees' => $result['total_employees']],
                "success",
                "Employee count retrieved successfully",
                200
            );
        } catch (\PDOException $e) {
            return $this->sendPayload(
                null,
                "failed",
                "Failed to retrieve employee count: " . $e->getMessage(),
                500
            );
        }
    }

    public function get_all_customers() {
        try {
            $sql = "SELECT id, first_name, last_name, email, phone, created_at FROM customers ORDER BY id DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->sendPayload(
                ['customers' => $customers],
                "success",
                "Customers retrieved successfully",
                200
            );
        } catch (\PDOException $e) {
            return $this->sendPayload(
                null,
                "failed",
                "Failed to retrieve customers: " . $e->getMessage(),
                500
            );
        }
    }

    public function get_all_employees() {
        try {
            $sql = "SELECT id, employee_id, first_name, last_name, email, phone, position, created_at FROM employees ORDER BY id DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->sendPayload(
                ['employees' => $employees],
                "success",
                "Employees retrieved successfully",
                200
            );
        } catch (\PDOException $e) {
            return $this->sendPayload(
                null,
                "failed",
                "Failed to retrieve employees: " . $e->getMessage(),
                500
            );
        }
    }
    
    public function get_all_services() {
        try {
            $sql = "SELECT id, name, description, price, duration_minutes, category, is_active, created_at, updated_at 
                   FROM services 
                   ORDER BY id DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->sendPayload(
                ['services' => $services],
                "success",
                "Services retrieved successfully",
                200
            );
        } catch (\PDOException $e) {
            return $this->sendPayload(
                null,
                "failed",
                "Failed to retrieve services: " . $e->getMessage(),
                500
            );
        }
    }

    public function get_all_bookings() {
        try {
            $sql = "SELECT 
                        b.id,
                        b.wash_date as washDate,
                        b.wash_time as washTime,
                        b.status,
                        b.price,
                        b.vehicle_type as vehicleType,
                        b.payment_type as paymentType,
                        b.notes,
                        b.nickname,
                        b.assigned_employee_id,
                        b.created_at as dateCreated,
                        TRIM(CONCAT(COALESCE(c.first_name,''), ' ', COALESCE(c.last_name,''))) as customerName,
                        c.first_name as firstName,
                        c.last_name as lastName,
                        c.phone,
                        s.name as serviceName,
                        s.description as serviceDescription,
                        s.duration_minutes as serviceDuration,
                        e.first_name as employee_first_name,
                        e.last_name as employee_last_name,
                        e.position as employee_position,
                        TRIM(CONCAT(COALESCE(e.first_name,''), ' ', COALESCE(e.last_name,''))) as assigned_employee_name,
                        CASE 
                            WHEN b.status = 'Rejected' AND b.notes LIKE '%Rejection reason:%' 
                            THEN TRIM(SUBSTRING_INDEX(b.notes, 'Rejection reason:', -1))
                            WHEN b.status = 'Rejected' AND b.notes LIKE '%Customer reason:%' 
                            THEN TRIM(SUBSTRING_INDEX(b.notes, 'Customer reason:', -1))
                            WHEN b.status = 'Rejected' AND b.notes IS NOT NULL AND b.notes != ''
                            THEN TRIM(b.notes)
                            ELSE NULL 
                        END as rejection_reason
                    FROM 
                        bookings b
                    LEFT JOIN 
                        services s ON b.service_id = s.id
                    LEFT JOIN 
                        customers c ON b.customer_id = c.id
                    LEFT JOIN 
                        employees e ON b.assigned_employee_id = e.id
                    ORDER BY 
                        b.wash_date DESC, b.wash_time DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->sendPayload(
                ['bookings' => $bookings],
                "success",
                "Bookings retrieved successfully",
                200
            );
        } catch (\PDOException $e) {
            return $this->sendPayload(
                null,
                "failed",
                "Failed to retrieve bookings: " . $e->getMessage(),
                500
            );
        }
    }

    public function get_bookings_by_customer($customerId) {
        try {
            $sql = "SELECT 
                        b.id,
                        b.wash_date as washDate,
                        b.wash_time as washTime,
                        b.status,
                        b.price,
                        b.vehicle_type as vehicleType,
                        b.payment_type as paymentType,
                        b.notes,
                        b.assigned_employee_id,
                        s.name as serviceName,
                        s.description as serviceDescription,
                        s.duration_minutes as serviceDuration,
                        e.first_name as employee_first_name,
                        e.last_name as employee_last_name,
                        e.position as employee_position,
                        CASE 
                            WHEN b.status = 'Rejected' AND b.notes LIKE '%Rejection reason:%' 
                            THEN TRIM(SUBSTRING_INDEX(b.notes, 'Rejection reason:', -1))
                            WHEN b.status = 'Rejected' AND b.notes LIKE '%Customer reason:%' 
                            THEN TRIM(SUBSTRING_INDEX(b.notes, 'Customer reason:', -1))
                            WHEN b.status = 'Rejected' AND b.notes IS NOT NULL AND b.notes != ''
                            THEN TRIM(b.notes)
                            ELSE NULL 
                        END as rejection_reason
                    FROM 
                        bookings b
                    JOIN 
                        services s ON b.service_id = s.id
                    LEFT JOIN 
                        employees e ON b.assigned_employee_id = e.id
                    WHERE 
                        b.customer_id = ?
                    ORDER BY 
                        b.wash_date DESC, b.wash_time DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$customerId]);
            $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->sendPayload(
                ['bookings' => $bookings],
                "success",
                "Bookings retrieved successfully",
                200
            );
        } catch (\PDOException $e) {
            return $this->sendPayload(
                null,
                "failed",
                "Failed to retrieve bookings: " . $e->getMessage(),
                500
            );
        }
    }

    public function get_bookings_by_employee($employeeId) {
        try {
            $sql = "SELECT 
                        b.id,
                        b.wash_date as washDate,
                        b.wash_time as washTime,
                        b.status,
                        b.price,
                        b.vehicle_type as vehicleType,
                        b.payment_type as paymentType,
                        b.notes,
                        b.assigned_employee_id,
                        s.name as serviceName,
                        s.description as serviceDescription,
                        s.duration_minutes as serviceDuration,
                        TRIM(CONCAT(COALESCE(c.first_name,''), ' ', COALESCE(c.last_name,''))) as customerName,
                        CASE 
                            WHEN b.status = 'Rejected' AND b.notes LIKE '%Rejection reason:%' 
                            THEN TRIM(SUBSTRING_INDEX(b.notes, 'Rejection reason:', -1))
                            WHEN b.status = 'Rejected' AND b.notes LIKE '%Customer reason:%' 
                            THEN TRIM(SUBSTRING_INDEX(b.notes, 'Customer reason:', -1))
                            WHEN b.status = 'Rejected' AND b.notes IS NOT NULL AND b.notes != ''
                            THEN TRIM(b.notes)
                            ELSE NULL 
                        END as rejection_reason
                    FROM 
                        bookings b
                    JOIN 
                        services s ON b.service_id = s.id
                    LEFT JOIN 
                        customers c ON b.customer_id = c.id
                    WHERE 
                        b.assigned_employee_id = ?
                    ORDER BY 
                        b.wash_date DESC, b.wash_time DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$employeeId]);
            $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->sendPayload(
                ['bookings' => $bookings],
                "success",
                "Employee bookings retrieved successfully",
                200
            );
        } catch (\PDOException $e) {
            return $this->sendPayload(
                null,
                "failed",
                "Failed to retrieve employee bookings: " . $e->getMessage(),
                500
            );
        }
    }

    public function get_booking_count() {
        try {
            $sql = "SELECT COUNT(*) as total_bookings FROM bookings";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $this->sendPayload(
                ['total_bookings' => $result['total_bookings']],
                "success",
                "Booking count retrieved successfully",
                200
            );
        } catch (\PDOException $e) {
            return $this->sendPayload(
                null,
                "failed",
                "Failed to retrieve booking count: " . $e->getMessage(),
                500
            );
        }
    }

    public function get_completed_booking_count() {
        try {
            $sql = "SELECT COUNT(*) as completed_bookings FROM bookings WHERE status = 'Completed'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $this->sendPayload(
                ['completed_bookings' => $result['completed_bookings']],
                "success",
                "Completed booking count retrieved successfully",
                200
            );
        } catch (\PDOException $e) {
            return $this->sendPayload(
                null,
                "failed",
                "Failed to retrieve completed booking count: " . $e->getMessage(),
                500
            );
        }
    }

    public function get_pending_booking_count() {
        try {
            $sql = "SELECT COUNT(*) as pending_bookings FROM bookings WHERE status = 'Pending'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $this->sendPayload(
                ['pending_bookings' => $result['pending_bookings']],
                "success",
                "Pending booking count retrieved successfully",
                200
            );
        } catch (\PDOException $e) {
            return $this->sendPayload(
                null,
                "failed",
                "Failed to retrieve pending booking count: " . $e->getMessage(),
                500
            );
        }
    }

    public function get_revenue_analytics() {
        try {
            // Get monthly revenue for the last 6 months
            $sql = "SELECT 
                        DATE_FORMAT(wash_date, '%Y-%m') as month,
                        SUM(price) as revenue,
                        COUNT(*) as bookings_count
                    FROM 
                        bookings 
                    WHERE 
                        wash_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                        AND status = 'Completed'
                    GROUP BY 
                        DATE_FORMAT(wash_date, '%Y-%m')
                    ORDER BY 
                        month ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $revenueData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->sendPayload(
                ['revenue_data' => $revenueData],
                "success",
                "Revenue analytics retrieved successfully",
                200
            );
        } catch (\PDOException $e) {
            return $this->sendPayload(
                null,
                "failed",
                "Failed to retrieve revenue analytics: " . $e->getMessage(),
                500
            );
        }
    }

    public function get_service_distribution() {
        try {
            $sql = "SELECT 
                        s.name as service_name,
                        COUNT(b.id) as booking_count,
                        AVG(b.price) as average_price,
                        SUM(b.price) as total_revenue
                    FROM 
                        services s
                    LEFT JOIN 
                        bookings b ON s.id = b.service_id
                    WHERE 
                        s.is_active = 1
                    GROUP BY 
                        s.id, s.name
                    ORDER BY 
                        booking_count DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $serviceData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->sendPayload(
                ['service_distribution' => $serviceData],
                "success",
                "Service distribution retrieved successfully",
                200
            );
        } catch (\PDOException $e) {
            return $this->sendPayload(
                null,
                "failed",
                "Failed to retrieve service distribution: " . $e->getMessage(),
                500
            );
        }
    }

    public function get_dashboard_summary() {
        try {
            $sql = "SELECT 
                        (SELECT COUNT(*) FROM customers) as total_customers,
                        (SELECT COUNT(*) FROM employees) as total_employees,
                        (SELECT COUNT(*) FROM bookings) as total_bookings,
                        (SELECT COUNT(*) FROM bookings WHERE status = 'Completed') as completed_bookings,
                        (SELECT COUNT(*) FROM bookings WHERE status = 'Pending') as pending_bookings,
                        (SELECT SUM(price) FROM bookings WHERE status = 'Completed' AND MONTH(wash_date) = MONTH(CURRENT_DATE)) as monthly_revenue";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $summary = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->sendPayload(
                ['dashboard_summary' => $summary],
                "success",
                "Dashboard summary retrieved successfully",
                200
            );
        } catch (\PDOException $e) {
            return $this->sendPayload(
                null,
                "failed",
                "Failed to retrieve dashboard summary: " . $e->getMessage(),
                500
            );
        }
    }

    public function get_inventory() {
        try {
            // Ensure table exists to avoid 500s on fresh databases
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS inventory (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                image_url VARCHAR(1024) NULL,
                stock INT NOT NULL DEFAULT 0,
                price DECIMAL(10,2) NOT NULL DEFAULT 0,
                category VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            // Ensure required columns exist on legacy tables
            $requiredColumns = [
                'image_url' => "ALTER TABLE inventory ADD COLUMN image_url VARCHAR(1024) NULL",
                'stock' => "ALTER TABLE inventory ADD COLUMN stock INT NOT NULL DEFAULT 0",
                'price' => "ALTER TABLE inventory ADD COLUMN price DECIMAL(10,2) NOT NULL DEFAULT 0",
                'category' => "ALTER TABLE inventory ADD COLUMN category VARCHAR(255) NULL",
            ];

            foreach ($requiredColumns as $column => $alterSql) {
                $checkSql = "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventory' AND COLUMN_NAME = ?";
                $checkStmt = $this->pdo->prepare($checkSql);
                $checkStmt->execute([$column]);
                $exists = (int)$checkStmt->fetchColumn() > 0;
                if (!$exists) {
                    try { $this->pdo->exec($alterSql); } catch (\PDOException $e) { /* ignore if race */ }
                }
            }

            $sql = "SELECT id, name, image_url, stock, price, category FROM inventory ORDER BY id DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->sendPayload(
                ['inventory' => $items],
                "success",
                "Inventory retrieved successfully",
                200
            );
        } catch (\PDOException $e) {
            // If something unexpected occurs, still return an empty list so UI can work
            return $this->sendPayload(
                ['inventory' => []],
                "failed",
                "Failed to retrieve inventory: " . $e->getMessage(),
                200
            );
        }
    }

    public function get_inventory_requests() {
        try {
            // Ensure inventory_requests table exists
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS inventory_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                item_id INT NOT NULL,
                item_name VARCHAR(255) NOT NULL,
                quantity INT NOT NULL,
                employee_id VARCHAR(50) NOT NULL,
                employee_name VARCHAR(255) NOT NULL,
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                request_date DATE NOT NULL,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $sql = "SELECT id, item_id, item_name, quantity, employee_id, employee_name, status, request_date, notes, created_at 
                    FROM inventory_requests ORDER BY created_at DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->sendPayload(
                ['inventory_requests' => $requests],
                "success",
                "Inventory requests retrieved successfully",
                200
            );
        } catch (\PDOException $e) {
            return $this->sendPayload(
                ['inventory_requests' => []],
                "failed",
                "Failed to retrieve inventory requests: " . $e->getMessage(),
                200
            );
        }
    }

    // New methods for the updated database schema
    public function get_vehicle_types() {
        try {
            $sql = "SELECT id, name, description, base_price_multiplier, is_active FROM vehicle_types WHERE is_active = 1 ORDER BY name";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $vehicleTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->sendPayload(
                ['vehicle_types' => $vehicleTypes],
                "success",
                "Vehicle types retrieved successfully",
                200
            );
        } catch (\PDOException $e) {
            return $this->sendPayload(
                null,
                "failed",
                "Failed to retrieve vehicle types: " . $e->getMessage(),
                500
            );
        }
    }

    public function get_payment_methods() {
        try {
            $sql = "SELECT id, name, description, is_active FROM payment_methods WHERE is_active = 1 ORDER BY name";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->sendPayload(
                ['payment_methods' => $paymentMethods],
                "success",
                "Payment methods retrieved successfully",
                200
            );
        } catch (\PDOException $e) {
            return $this->sendPayload(
                null,
                "failed",
                "Failed to retrieve payment methods: " . $e->getMessage(),
                500
            );
        }
    }

    public function get_time_slots() {
        try {
            $sql = "SELECT id, start_time, end_time, max_bookings, is_active FROM time_slots WHERE is_active = 1 ORDER BY start_time";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $timeSlots = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->sendPayload(
                ['time_slots' => $timeSlots],
                "success",
                "Time slots retrieved successfully",
                200
            );
        } catch (\PDOException $e) {
            return $this->sendPayload(
                null,
                "failed",
                "Failed to retrieve time slots: " . $e->getMessage(),
                500
            );
        }
    }

    public function get_available_time_slots($date) {
        try {
            $sql = "SELECT 
                        ts.id,
                        ts.start_time,
                        ts.end_time,
                        ts.max_bookings,
                        (ts.max_bookings - COALESCE(COUNT(b.id), 0)) as available_slots
                    FROM 
                        time_slots ts
                    LEFT JOIN 
                        bookings b ON ts.start_time = b.wash_time 
                        AND b.wash_date = ? 
                        AND b.status IN ('Pending', 'Confirmed')
                    WHERE 
                        ts.is_active = 1
                    GROUP BY 
                        ts.id, ts.start_time, ts.end_time, ts.max_bookings
                    HAVING 
                        available_slots > 0
                    ORDER BY 
                        ts.start_time";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$date]);
            $availableSlots = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->sendPayload(
                ['available_time_slots' => $availableSlots],
                "success",
                "Available time slots retrieved successfully",
                200
            );
        } catch (\PDOException $e) {
            return $this->sendPayload(
                null,
                "failed",
                "Failed to retrieve available time slots: " . $e->getMessage(),
                500
            );
        }
    }



    public function get_service_categories() {
        try {
            $sql = "SELECT id, name, description, is_active FROM service_categories WHERE is_active = 1 ORDER BY name";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->sendPayload(
                ['service_categories' => $categories],
                "success",
                "Service categories retrieved successfully",
                200
            );
        } catch (\PDOException $e) {
            return $this->sendPayload(
                null,
                "failed",
                "Failed to retrieve service categories: " . $e->getMessage(),
                500
            );
        }
    }

    public function get_customer_feedback($limit = 10) {
        try {
            $sql = "SELECT 
                        cf.id,
                        cf.rating,
                        cf.comment,
                        cf.is_public,
                        cf.created_at,
                        c.first_name,
                        c.last_name,
                        s.name as service_name
                    FROM 
                        customer_feedback cf
                    JOIN 
                        customers c ON cf.customer_id = c.id
                    JOIN 
                        bookings b ON cf.booking_id = b.id
                    JOIN 
                        services s ON b.service_id = s.id
                    WHERE 
                        cf.is_public = 1
                    ORDER BY 
                        cf.created_at DESC
                    LIMIT ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$limit]);
            $feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->sendPayload(
                ['customer_feedback' => $feedback],
                "success",
                "Customer feedback retrieved successfully",
                200
            );
        } catch (\PDOException $e) {
            return $this->sendPayload(
                null,
                "failed",
                "Failed to retrieve customer feedback: " . $e->getMessage(),
                500
            );
        }
    }

    public function get_employee_schedules($employeeId = null, $date = null) {
        try {
            $sql = "SELECT 
                        es.id,
                        es.work_date,
                        es.start_time,
                        es.end_time,
                        es.is_available,
                        e.first_name,
                        e.last_name,
                        e.position
                    FROM 
                        employee_schedules es
                    JOIN 
                        employees e ON es.employee_id = e.id";
            
            $params = [];
            if ($employeeId) {
                $sql .= " WHERE es.employee_id = ?";
                $params[] = $employeeId;
            }
            if ($date) {
                $sql .= $employeeId ? " AND es.work_date = ?" : " WHERE es.work_date = ?";
                $params[] = $date;
            }
            
            $sql .= " ORDER BY es.work_date, es.start_time";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->sendPayload(
                ['employee_schedules' => $schedules],
                "success",
                "Employee schedules retrieved successfully",
                200
            );
        } catch (\PDOException $e) {
            return $this->sendPayload(
                null,
                "failed",
                "Failed to retrieve employee schedules: " . $e->getMessage(),
                500
            );
        }
    }

    public function get_system_settings() {
        try {
            $sql = "SELECT setting_key, setting_value, description FROM system_settings ORDER BY setting_key";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convert to associative array for easier access
            $settingsArray = [];
            foreach ($settings as $setting) {
                $settingsArray[$setting['setting_key']] = $setting['setting_value'];
            }
            
            return $this->sendPayload(
                ['system_settings' => $settingsArray],
                "success",
                "System settings retrieved successfully",
                200
            );
        } catch (\PDOException $e) {
            return $this->sendPayload(
                null,
                "failed",
                "Failed to retrieve system settings: " . $e->getMessage(),
                500
            );
        }
    }



    public function get_booking_details($bookingId) {
        try {
            $sql = "SELECT 
                        b.id,
                        b.wash_date,
                        b.wash_time,
                        b.status,
                        b.price,
                        b.vehicle_type,
                        b.payment_type,
                        b.nickname,
                        b.phone,
                        b.notes,
                        b.created_at,
                        b.updated_at,
                        c.first_name,
                        c.last_name,
                        c.email,
                        s.name as service_name,
                        s.description as service_description,
                        s.duration_minutes,
                        s.category
                    FROM 
                        bookings b
                    JOIN 
                        customers c ON b.customer_id = c.id
                    JOIN 
                        services s ON b.service_id = s.id
                    WHERE 
                        b.id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$booking) {
                return $this->sendPayload(
                    null,
                    "failed",
                    "Booking not found",
                    404
                );
            }
            
            return $this->sendPayload(
                ['booking' => $booking],
                "success",
                "Booking details retrieved successfully",
                200
            );
        } catch (\PDOException $e) {
            return $this->sendPayload(
                null,
                "failed",
                "Failed to retrieve booking details: " . $e->getMessage(),
                500
            );
        }
    }

    public function get_booking_history($bookingId) {
        try {
            $sql = "SELECT 
                        id,
                        status_from,
                        status_to,
                        changed_by,
                        notes,
                        created_at
                    FROM 
                        booking_history 
                    WHERE 
                        booking_id = ? 
                    ORDER BY 
                        created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$bookingId]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->sendPayload(
                ['booking_history' => $history],
                "success",
                "Booking history retrieved successfully",
                200
            );
        } catch (\PDOException $e) {
            return $this->sendPayload(
                null,
                "failed",
                "Failed to retrieve booking history: " . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get all contact enquiries
     */
    public function get_contact_enquiries() {
        try {
            $sql = "SELECT 
                        id,
                        name,
                        email,
                        subject,
                        message,
                        created_at,
                        status
                    FROM 
                        contact_messages 
                    ORDER BY 
                        created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $enquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->sendPayload(
                $enquiries,
                "success",
                "Contact enquiries retrieved successfully",
                200
            );
        } catch (\PDOException $e) {
            error_log("Error getting contact enquiries: " . $e->getMessage());
            return $this->sendPayload(
                null,
                "failed",
                "Failed to retrieve contact enquiries: " . $e->getMessage(),
                500
            );
        }
    }
}
?>