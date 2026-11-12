<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['pharmacist']); // Pharmacist and admin only

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $stmt = $conn->prepare("DELETE FROM medicines WHERE medicine_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: medicines.php");
exit();
?>
