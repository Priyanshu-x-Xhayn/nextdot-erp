<?php
include './backend/connect.php';
session_start();

// Check if the request is valid
if (isset($_REQUEST['id']) && isset($_REQUEST['status'])) {
    $task_id = $_REQUEST['id'];
    $status = $_REQUEST['status'];
    
    // Prepare the update query
    $update_query = "UPDATE tasks SET status = ?, completion_date = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    
    // Set completion date if task is being marked as completed
    $completion_date = ($status == 2) ? date('Y-m-d') : NULL;
    
    $stmt->bind_param("isi", $status, $completion_date, $task_id);
    
    // Execute the update
    if ($stmt->execute()) {
        // Check if this is an AJAX request
        if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] === true) {
            echo "success";
        } else {
            // Redirect back to the tasks page
            header("Location: daily_tasks.php");
            exit();
        }
    } else {
        echo "Error updating task: " . $conn->error;
    }
    
    $stmt->close();
} else {
    echo "Invalid request parameters";
}

$conn->close();
?>