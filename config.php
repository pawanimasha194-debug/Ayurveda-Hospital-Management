<?php
// ============================================================
// Database Connection Config
// Ayurveda Hospital Prescription and Inventory Management System
// ============================================================

$host = "localhost";
$username = "root";       // XAMPP default username
$password = "";           // XAMPP default password (empty)
$database = "ayurveda_hospital_db";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session (used across all pages for login state)
session_start();

// ============================================================
// Role-based access control
// Call this at the top of any page that should be restricted.
// Example: restrictTo(['admin', 'doctor']);
// 'admin' is always allowed automatically, in addition to the roles passed.
// ============================================================
function restrictTo($allowedRoles) {
    if (!isset($_SESSION['role'])) {
        header("Location: login.php");
        exit();
    }

    $role = $_SESSION['role'];

    if ($role === 'admin' || in_array($role, $allowedRoles)) {
        return; // access granted
    }

    // Access denied - show a simple message
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>
    <title>Access Denied</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f2f5f0; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .box { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); text-align: center; max-width: 400px; }
        .box h2 { color: #c0392b; }
        .box a { display: inline-block; margin-top: 15px; background: #2e5339; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; }
    </style></head><body>
    <div class='box'>
        <h2>Access Denied</h2>
        <p>Your role (<b>" . htmlspecialchars($role) . "</b>) does not have permission to view this page.</p>
        <a href='dashboard.php'>&larr; Back to Dashboard</a>
    </div>
    </body></html>";
    exit();
}
?>
