<?php
include './backend/connect.php';

// Ensure the uploads directory exists
$targetDir = "./backend/uploads/";
if (!file_exists($targetDir)) {
    mkdir($targetDir, 0755, true);
}

if(isset($_POST['Update'])) {
    $id = $_POST['Id'];
    $joining_date = $_POST['joining_date'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];
    $depart = $_POST['depart']; // This should now be the department ID
    $desig = $_POST['desig'];
    $role = $_POST['role'];
    $username = $_POST['username'];
    
    $updatePassword = false;
    $password = "";
    
    // Only update password if a new one is provided
    if(!empty($_POST['password'])) {
        $updatePassword = true;
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    }
    
    // Check if file is uploaded
    $fileName = '';
    if(isset($_FILES['Image']) && $_FILES['Image']['error'] == 0) {
        $targetDir = "./backend/uploads/";
        $fileName = basename($_FILES["Image"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        
        // Upload file to server
        if(move_uploaded_file($_FILES["Image"]["tmp_name"], $targetFilePath)) {
            // Update with new file
            if($updatePassword) {
                $query = "UPDATE employee SET joining_date='$joining_date', name='$name', email='$email', 
                        contact='$contact', depart='$depart', desig='$desig', role='$role', 
                        username='$username', password='$password', Image='$fileName' WHERE id='$id'";
            } else {
                $query = "UPDATE employee SET joining_date='$joining_date', name='$name', email='$email', 
                        contact='$contact', depart='$depart', desig='$desig', role='$role', 
                        username='$username', Image='$fileName' WHERE id='$id'";
            }
        }
    } else {
        // Update without file
        if($updatePassword) {
            $query = "UPDATE employee SET joining_date='$joining_date', name='$name', email='$email', 
                    contact='$contact', depart='$depart', desig='$desig', role='$role', 
                    username='$username', password='$password' WHERE id='$id'";
        } else {
            $query = "UPDATE employee SET joining_date='$joining_date', name='$name', email='$email', 
                    contact='$contact', depart='$depart', desig='$desig', role='$role', 
                    username='$username' WHERE id='$id'";
        }
    }
    
    $result = mysqli_query($conn, $query);
    
    if($result) {
        // If the update was successful and this is the logged-in user, update their session
        if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id) {
            // If a new image was uploaded, update the session
            if(!empty($fileName)) {
                $_SESSION['user_image'] = $fileName;
            }
            
            // Update other session variables that might have changed
            $_SESSION['name'] = $name;
            $_SESSION['role'] = $role;
            
            // Get the department name for the session
            $dept_query = "SELECT department_name FROM departments WHERE id = '$depart'";
            $dept_result = mysqli_query($conn, $dept_query);
            if($dept_result && mysqli_num_rows($dept_result) > 0) {
                $dept_row = mysqli_fetch_assoc($dept_result);
                $_SESSION['department'] = $dept_row['department_name'];
            }
        }
        
        echo "<script>
                alert('Employee details updated successfully');
                window.location.href='employee.php';
              </script>";
    } else {
        echo "<script>
                alert('Error updating employee: " . mysqli_error($conn) . "');
                window.location.href='employee.php';
              </script>";
    }
}
?>