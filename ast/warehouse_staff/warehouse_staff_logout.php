
<?php
include '../components/connect.php';

session_start();

// Update staff availability status to false when logging out
if(isset($_SESSION['staff_id'])) {
    $staff_id = $_SESSION['staff_id'];
    $update_availability = $conn->prepare("UPDATE `warehouse_staff` SET is_available = ? WHERE staff_id = ?");
    $update_availability->execute([0, $staff_id]);
}

// Unset and destroy session
session_unset();
session_destroy();

// Redirect to login page
header('location:index.php');
?>