<?php
include './backend/connect.php';
include 'auth_check.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $task_name = $_POST['task_name'];
    $description = $_POST['description'];
    $assigned_to = $_POST['assigned_to'];
    $start_date = $_POST['start_date'];
    $due_date = $_POST['due_date'];
    $priority = $_POST['priority'];
    $completion_date = $_POST['completion_date'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];

    $today = new DateTime();
    $due = new DateTime($due_date);
    $interval = $today->diff($due);
    $is_overdue = ($today > $due && $status != 'Completed') ? 1 : 0;
    $pending_days = $interval->days;
    $pending_age = $interval->format('%a Days');

    $sql = "UPDATE tasks SET
              task_name='$task_name',
              description='$description',
              assigned_to='$assigned_to',
              start_date='$start_date',
              due_date='$due_date',
              priority='$priority',
              completion_date='$completion_date',
              status='$status',
              notes='$notes',
              is_overdue='$is_overdue',
              pending_days='$pending_days',
              pending_age='$pending_age'
            WHERE id='$id'";
    
    if (mysqli_query($conn, $sql)) {
        echo "Task updated successfully!";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
