<?php
include './backend/connect.php';
session_start();

if(isset($_POST['department_id']) && !empty($_POST['department_id'])) {
    $department_id = $_POST['department_id'];
    
    // Get employees from the selected department
    $query = "SELECT id, name FROM employee WHERE depart = ? ORDER BY name";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Build options HTML
    $options = '<option value="">Select Assignee</option>';
    while($row = $result->fetch_assoc()) {
        $options .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
    }
    
    echo $options;
} else {
    // Return all employees if no department selected
    $query = "SELECT id, name FROM employee ORDER BY name";
    $result = $conn->query($query);
    
    $options = '<option value="">Select Assignee</option>';
    while($row = $result->fetch_assoc()) {
        $options .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
    }
    
    echo $options;
}
?>