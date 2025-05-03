<?php

if (isset($_POST['delete'])) {
    $id = $_POST['Id'];
    // $image = $_POST['pdf'];
  
    $delete_query = "DELETE FROM raw_material WHERE id='$id'";
    $delete_query_run = mysqli_query($conn, $delete_query);
  
    if ($delete_query_run) {
      // unlink("Company_Management/uploads/".$pdf);
      $_SESSION['status'] = "Image deleted sucessfully !";
    } else {
      $_SESSION['status'] = "Image not deleted successfully !";
    }
  }
?>