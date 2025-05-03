<?php
if (isset($_POST["Update"])) {
    $id = $_POST['Id'];
    $entry_date = $_POST['entry_date'];
    $name = $_POST['name'];
    $Email = $_POST['Email'];
    $contact = $_POST['contact'];
    $desig = $_POST['desig'];
    $manage_depart = $_POST['manage_depart'];

    // Update query without image field
    $sql = "UPDATE raw_material SET 
    entry_date = '$entry_date', 
    name = '$name', 
    Email = '$Email', 
    contact = '$contact', 
    desig = '$desig', 
    manage_depart = '$manage_depart' 
WHERE id = '$id'";  // Ensuring a valid WHERE clause
    if ($conn->query($sql) === TRUE) {
        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}
?>
