<?php
include './backend/connect.php';

$selected_department = "";

// Fetch last assigned priority from database
$sql_fetch = "SELECT priori FROM tasks ORDER BY id DESC LIMIT 1";
$result = $conn->query($sql_fetch);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $selected_Priority = $row['priori'] ?? "";
}

// Fetch the records from the database
$result = $conn->query("SELECT * FROM tasks"); 

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    $task_name = $_POST['task_name'];
    $description = $_POST['description'];
    $assigned_to = $_POST['assigned_to'];
    $start_date = $_POST['start_date'];
    $due_date = $_POST['due_date']; 
    $completion_date = $_POST['completion_date'];
    $status = $_POST['status'];
    $priori = $_POST['Priority']; 
    $notes = $_POST['notes']; 

    // Corrected column name from 'Priority' to 'priori'
    $sql = "INSERT INTO tasks (task_name, description, assigned_to, start_date, due_date, completion_date,status, priori, notes) 
            VALUES ('$task_name', '$description', '$assigned_to', '$start_date', '$due_date', '$completion_date',' $status', '$priori', '$notes')";

    if ($conn->query($sql) === TRUE) {
        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}
?>

