<?php
include './backend/connect.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_task"])) {
    $task_id = $_POST['task_id'];
    $task_name = $_POST['task_name'];
    $description = $_POST['description'];
    $assigned_to = $_POST['assigned_to'];
    $project_id = $_POST['project_id'];
    $start_date = $_POST['start_date'];
    $due_date = $_POST['due_date'];
    $priority = $_POST['priority'];
    $status = $_POST['status'];
    $hyperlinks = isset($_POST['hyperlinks']) ? $_POST['hyperlinks'] : '';
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    
    // Check if user has permission to update this task
    $permission_check = "SELECT t.id, t.assigned_to, e.depart as assignee_dept 
                        FROM tasks t 
                        JOIN employee e ON t.assigned_to = e.id 
                        WHERE t.id = ?";
    $stmt = $conn->prepare($permission_check);
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error_message'] = 'Task not found';
        header('Location: tasks.php');
        exit;
    }
    
    $task = $result->fetch_assoc();
    
    // Get user's department
    $user_dept_query = "SELECT depart FROM employee WHERE id = ?";
    $stmt = $conn->prepare($user_dept_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_dept_result = $stmt->get_result();
    $user_dept = $user_dept_result->fetch_assoc()['depart'];
    
    // Check permissions based on role
    $can_update = false;
    
    if ($user_role == 'Admin' || $user_role == 'Manager') {
        $can_update = true; // Admin and Manager can update any task
    } else if ($user_role == 'Team Lead' && $task['assignee_dept'] == $user_dept) {
        $can_update = true; // Team Lead can update tasks in their department
    } else if ($task['assigned_to'] == $user_id) {
        $can_update = true; // Users can update their own tasks
    }
    
    if (!$can_update) {
        $_SESSION['error_message'] = 'You do not have permission to update this task';
        header('Location: tasks.php');
        exit;
    }
    
    // If status is changing to completed, set completion date
    $completion_date = null;
    if ($status == '2') {
        $completion_date = date('Y-m-d');
        
        // Update task with completion date
        $sql = "UPDATE tasks SET 
                task_name = ?, 
                description = ?, 
                assigned_to = ?, 
                project_id = ?, 
                start_date = ?, 
                due_date = ?, 
                priori = ?, 
                status = ?, 
                hyperlinks = ?, 
                notes = ?,
                completion_date = ?
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiisssssssi", $task_name, $description, $assigned_to, $project_id, 
                        $start_date, $due_date, $priority, $status, $hyperlinks, $notes, 
                        $completion_date, $task_id);
    } else {
        // Regular update without completion date
        $sql = "UPDATE tasks SET 
                task_name = ?, 
                description = ?, 
                assigned_to = ?, 
                project_id = ?, 
                start_date = ?, 
                due_date = ?, 
                priori = ?, 
                status = ?, 
                hyperlinks = ?, 
                notes = ?
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiissssssi", $task_name, $description, $assigned_to, $project_id, 
                        $start_date, $due_date, $priority, $status, $hyperlinks, $notes, $task_id);
    }
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Task updated successfully';
    } else {
        $_SESSION['error_message'] = 'Error updating task: ' . $conn->error;
    }
    
    header('Location: tasks.php');
    exit;
}

// If not a POST request or missing update_task parameter
header('Location: tasks.php');
exit;
?>