<?php
include_once ('backend/connect.php');

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    // $image = $_POST['pdf'];
    
    $delete_query = "DELETE FROM employee WHERE id='$id'";
    $delete_query_run = mysqli_query($conn, $delete_query);
  
    if ($delete_query_run) {
      // unlink("Company_Management/uploads/".$pdf);
      $_SESSION['status'] = "Image deleted sucessfully !";

      echo '
      <script>
      window.location.href="manage_depart.php";
      </script>
      ';

    } else {
      $_SESSION['status'] = "Image not deleted successfully !";
    }
  }


  if (isset($_POST['Id'])) {
    $id = $_POST['Id'];
    // $image = $_POST['pdf'];
    
    $delete_query = "DELETE FROM employee WHERE id='$id'";
    $delete_query_run = mysqli_query($conn, $delete_query);
  
    if ($delete_query_run) {
      // unlink("Company_Management/uploads/".$pdf);
      $_SESSION['status'] = "Image deleted sucessfully !";

      echo '
      <script>
      window.location.href="employee.php";
      </script>
      ';

    } else {
      $_SESSION['status'] = "Image not deleted successfully !";
    }
  }



?>