<?php
include './backend/connect.php';
include 'auth_check.php';

// Check if request is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date_of = $_POST['date_of'];
    $name_of = $_POST['name_of'];
    $contact = $_POST['contact'];
    $email = $_POST['email'];
    $designation = $_POST['designation'];
    
    // Sanitize inputs
    $date_of = filter_var($date_of, FILTER_SANITIZE_STRING);
    $name_of = filter_var($name_of, FILTER_SANITIZE_STRING);
    $contact = filter_var($contact, FILTER_SANITIZE_STRING);
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    $designation = filter_var($designation, FILTER_SANITIZE_STRING);
    
    // Insert department into database
    $insert_sql = "INSERT INTO add_depart (date_of, name_of, contact, email, designation) 
                  VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("sssss", $date_of, $name_of, $contact, $email, $designation);
    
    $response = array();
    
    if ($stmt->execute()) {
        $response['status'] = 'success';
        $response['message'] = 'Department added successfully!';
        $response['department_id'] = $conn->insert_id;
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Error adding department: ' . $conn->error;
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