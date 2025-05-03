<?php
include './backend/connect.php';
include 'auth_check.php';

if (isset($_POST['delete'])) {
    $id = $_POST['id'];
    $query = "DELETE FROM tasks WHERE id = $id";
    if (mysqli_query($conn, $query)) {
        header("Location: task.php"); // or your main page
        exit;
    } else {
        echo "Error deleting record: " . mysqli_error($conn);
    }
}
?>
