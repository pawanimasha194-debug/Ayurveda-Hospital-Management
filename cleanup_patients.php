<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
restrictTo(['reception', 'admin']);

$today = date('Y-m-d');
$one_year_ago = date('Y-m-d', strtotime('-1 year'));

// Delete temporary patients who haven't visited in 1 year, 
// AND who don't have any prescriptions (to protect medical history)
$sql = "DELETE p FROM patients p 
        LEFT JOIN prescriptions pr ON p.patient_id = pr.patient_id 
        WHERE p.patient_type = 'temporary' 
        AND p.last_visit < '$one_year_ago' 
        AND pr.prescription_id IS NULL";

if ($conn->query($sql)) {
    $deleted_count = $conn->affected_rows;
    echo "<script>
            alert('Cleanup Successful! Removed $deleted_count inactive temporary patient records.');
            window.location.href = 'reception_dashboard.php';
          </script>";
} else {
    echo "Error: " . $conn->error;
}
?>