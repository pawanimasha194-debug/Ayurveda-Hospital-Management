<?php
// Run this ONE TIME in browser to create/update the admin login.
// After running, DELETE this file for security.

include 'config.php';

$username = "admin";
$plain_password = "admin123";   // change this to whatever you want
$hashed = password_hash($plain_password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
$stmt->bind_param("ss", $hashed, $username);
$stmt->execute();

echo "Admin password updated successfully. Username: admin | Password: $plain_password";
echo "<br><b>Now delete create_admin.php for security.</b>";
?>
