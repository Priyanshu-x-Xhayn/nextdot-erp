<?php
include './backend/connect.php';
include 'auth_check.php';

// Check if request is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $employee_id = $_POST['employee_id'];
    $new_role = $_POST['new_role'];
    
    // Sanitize inputs
    $employee_id = filter_var($employee_id, FILTER_SANITIZE_NUMBER_INT);
    $new_role = filter_var($new_role, FILTER_SANITIZE_STRING);
    
    // Update role in database
    $update_sql = "UPDATE employee SET role = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $new_role, $employee_id);
    
    $response = array();
    
    if ($stmt->execute()) {
        $response['status'] = 'success';
        $response['message'] = 'Employee role updated successfully!';
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Error updating role: ' . $conn->error;
    }
    
    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// If not POST request, return error
$response = array(
    'status' => 'error',
    'message' => 'Invalid request method'
);

header('Content-Type: application/json');
echo json_encode($response);
?>