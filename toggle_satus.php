<?php
include './backend/connect.php';
include 'auth_check.php';

if (isset($_POST['id']) && isset($_POST['status'])) {
    $id = $_POST['id'];
    $status = $_POST['status'] === 'Completed' ? 'Pending' : 'Completed';
    $sql = "UPDATE tasks SET status='$status' WHERE id='$id'";
    if (mysqli_query($conn, $sql)) {
        echo "Status toggled to $status";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
