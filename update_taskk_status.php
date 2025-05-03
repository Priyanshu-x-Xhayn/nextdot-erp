<?php
include './backend/connect.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit;
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Check if required parameters are provided
if (isset($_POST['task_id']) && isset($_POST['status'])) {
    $task_id = $_POST['task_id'];
    $status = $_POST['status'];
    
    // Validate status value
    if (!in_array($status, ['0', '1', '2'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid status value']);
        exit;
    }
    
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
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Task not found']);
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
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'You do not have permission to update this task']);
        exit;
    }
    
    // Update task status
    $update_sql = "UPDATE tasks SET status = ? WHERE id = ?";
    
    // If marking as completed, also set completion date
    if ($status == '2') {
        $update_sql = "UPDATE tasks SET status = ?, completion_date = CURDATE() WHERE id = ?";
    }
    
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $status, $task_id);
    
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
}
?>