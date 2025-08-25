<?php
require_once "./config/database.php";

try {
    // Create a new connection
    $connection = new Connection();
    $pdo = $connection->connect();
    
    // Try a simple query
    $stmt = $pdo->query("SELECT 1");
    
    if ($stmt) {
        echo "Database connection successful! Your database is connected.";
    }
} catch (\PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
}
?>